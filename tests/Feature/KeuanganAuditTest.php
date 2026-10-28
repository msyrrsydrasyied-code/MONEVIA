<?php

namespace Tests\Feature;

use App\Models\Keuangan;
use App\Models\Kategori;
use App\Models\TargetTabungan;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\DailyTargetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Audit Tahap 1: konsistensi angka keuangan + ownership antar user.
 *
 * Test di sini deliberately memverifikasi NILAI hasil perhitungan, bukan hanya
 * HTTP 200, karena bug pembagian uang dan saldo salah lolos dari test yang hanya
 * mengecek status respons.
 */
class KeuanganAuditTest extends TestCase
{
    use RefreshDatabase;

    private function kategori(string $nama = 'Makan'): Kategori
    {
        return Kategori::firstOrCreate(['nama_kategori' => $nama]);
    }

    private function catat(User $user, int $jumlah, string $jenis = 'pengeluaran', ?string $tanggal = null): Transaksi
    {
        return Transaksi::create([
            'user_id' => $user->id,
            'kategori_id' => $this->kategori()->id,
            'nama' => 'Trx ' . $jumlah,
            'jumlah' => $jumlah,
            'jenis' => $jenis,
            'tanggal' => $tanggal ?? now()->toDateString(),
        ]);
    }

    /** Anggaran manual dengan alokasi proporsional 50/30/20. */
    private function anggaran(User $user, int $uang, ?int $periode = 30, ?string $mulai = null): Keuangan
    {
        $kebutuhan = (int) round($uang * 0.5);
        $tabungan = (int) round($uang * 0.3);

        return Keuangan::create([
            'user_id' => $user->id,
            'jumlah_uang' => $uang,
            'pengeluaran_wajib' => $kebutuhan,
            'target_tabungan' => $tabungan,
            'budget_hiburan' => $uang - $kebutuhan - $tabungan,
            'target_hari' => $periode,
            'tanggal_mulai_target' => $mulai ?? now()->toDateString(),
        ]);
    }

    // =========================================================
    // A. BATAS HARIAN — nilai hasil, sesuai konsep resmi
    // =========================================================

    public function test_batas_harian_sesuai_konsep_sisa_uang_dibagi_sisa_hari(): void
    {
        $user = User::factory()->create();
        $k = $this->anggaran($user, 1000000, 30);

        $t = DailyTargetService::calculateDailyTarget($k, $user->id);
        $this->assertSame(1000000.0, (float) $t['sisa_uang']);
        $this->assertSame(30, $t['sisa_hari']);
        $this->assertSame(33333, $t['batas_harian']);

        // Setelah belanja 20.000 dan 1 hari berlalu: 980.000 / 29 = 33.793
        $this->catat($user, 20000);
        $k->update(['tanggal_mulai_target' => now()->subDay()->toDateString()]);

        $t2 = DailyTargetService::calculateDailyTarget($k->fresh(), $user->id);
        $this->assertSame(980000.0, (float) $t2['sisa_uang']);
        $this->assertSame(29, $t2['sisa_hari']);
        $this->assertSame(33793, $t2['batas_harian']);
    }

    public function test_tidak_ada_division_by_zero_di_seluruh_kondisi_batas(): void
    {
        $kondisi = [
            'sisa hari 0'          => [1000000, 30, now()->subDays(30)->toDateString()],
            'periode lewat jauh'   => [1000000, 7,  now()->subDays(90)->toDateString()],
            'target hari null'     => [1000000, null, now()->toDateString()],
            'tanggal mulai null'   => [1000000, 30, null],
            'saldo nol'            => [0, 30, now()->toDateString()],
            'uang 1 rupiah'        => [1, 30, now()->toDateString()],
            'periode 1 hari'       => [1000000, 1, now()->toDateString()],
        ];

        foreach ($kondisi as $label => [$uang, $periode, $mulai]) {
            // Satu user per kondisi supaya tidak melanggar UNIQUE keuangan.user_id.
            $user = User::factory()->create();
            $k = $this->anggaran($user, $uang, $periode, $mulai);
            $t = DailyTargetService::calculateDailyTarget($k, $user->id);

            $this->assertIsInt($t['batas_harian'], "batas harian harus int pada kondisi: $label");
            $this->assertGreaterThanOrEqual(0, $t['batas_harian'], "batas harian negatif pada: $label");

            // Status juga harus selalu terisi dan dikenal.
            $status = DailyTargetService::statusHarian($t, 0);
            $this->assertNotEmpty($status['status'], "status kosong pada: $label");
            $this->assertArrayHasKey('pesan', $status);
        }
    }

    public function test_periode_selesai_memberi_batas_nol_bukan_error(): void
    {
        $user = User::factory()->create();
        $k = $this->anggaran($user, 1000000, 30, now()->subDays(30)->toDateString());

        $t = DailyTargetService::calculateDailyTarget($k, $user->id);
        $this->assertTrue($t['periode_selesai']);
        $this->assertSame(0, $t['sisa_hari']);
        $this->assertSame(0, $t['batas_harian']);
        $this->assertSame('periode_selesai', DailyTargetService::statusHarian($t, 0)['status']);
    }

    public function test_pengeluaran_melebihi_uang_ditangani_tanpa_error(): void
    {
        $user = User::factory()->create();
        $k = $this->anggaran($user, 10000, 30);
        $this->catat($user, 50000);

        $t = DailyTargetService::calculateDailyTarget($k, $user->id);

        // Saldo negatif tidak boleh disembunyikan jadi 0.
        $this->assertSame(-40000.0, (float) $t['saldo_aktual']);
        $this->assertSame(-40000.0, (float) $t['saldo_tersedia']);
        $this->assertSame(-40000.0, (float) $t['sisa_uang']);

        // Batas harian tetap tidak boleh negatif.
        $this->assertSame(0, $t['batas_harian']);
        $this->assertSame('boros', DailyTargetService::statusHarian($t, 50000)['status']);

        $this->actingAs($user)->get(route('dashboard'))->assertOk();
        $this->actingAs($user)->get(route('target-harian.index'))->assertOk();
    }

    // =========================================================
    // B. SALDO AKTUAL — create / edit / delete
    // =========================================================

    public function test_saldo_berubah_sesuai_transaksi_aksi(): void
    {
        $user = User::factory()->create();
        $k = $this->anggaran($user, 1000000, 30);

        // CASE 1: tanpa transaksi
        $this->assertSame(1000000.0, (float) DailyTargetService::hitungSisaUang($k, $user->id));

        // CASE 2: transaksi 20.000
        $t = $this->catat($user, 20000);
        $this->assertSame(980000.0, (float) DailyTargetService::hitungSisaUang($k, $user->id));

        // CASE 3: diubah jadi 50.000
        $t->update(['jumlah' => 50000]);
        $this->assertSame(950000.0, (float) DailyTargetService::hitungSisaUang($k, $user->id));

        // CASE 4: dihapus -> kembali seperti semula
        $t->delete();
        $this->assertSame(1000000.0, (float) DailyTargetService::hitungSisaUang($k, $user->id));
    }

    public function test_pemasukan_menambah_saldo_dan_pengeluaran_mengurangi(): void
    {
        $user = User::factory()->create();
        $k = $this->anggaran($user, 1000000, 30);

        $this->catat($user, 250000, 'pemasukan');
        $this->assertSame(1250000.0, (float) DailyTargetService::hitungSisaUang($k, $user->id));

        $this->catat($user, 75000, 'pengeluaran');
        $this->assertSame(1175000.0, (float) DailyTargetService::hitungSisaUang($k, $user->id));
    }

    public function test_halaman_menampilkan_saldo_konsisten_setelah_edit_transaksi(): void
    {
        $user = User::factory()->create();
        $k = $this->anggaran($user, 1000000, 30);
        $t = $this->catat($user, 20000);

        $this->actingAs($user)->post(route('transaksi.update', $t->id), [
            'nama' => 'Trx 20.000',
            'jumlah' => 50000,
            'jenis' => 'pengeluaran',
            'kategori_id' => $this->kategori()->id,
            'tanggal' => now()->toDateString(),
        ])->assertRedirect('/transaksi');

        $this->assertSame(950000.0, (float) DailyTargetService::hitungSisaUang($k, $user->id));
        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('950.000');
    }

    // =========================================================
    // C. ALOKASI — plan vs nominal uang
    // =========================================================

    public function test_alokasi_setup_anggaran_selalu_konsisten_dengan_jumlah_uang(): void
    {
        foreach ([1, 10000, 50000, 100000, 600000, 1000000, 1234567] as $uang) {
            $this->actingAs($user = User::factory()->create())->post(route('setup-anggaran.simpan'), [
                'jumlah_uang' => $uang,
                'periode_hari' => 30,
                'persen_kebutuhan' => 50,
                'persen_tabungan' => 30,
                'persen_hiburan' => 20,
            ]);

            $k = Keuangan::where('user_id', $user->id)->firstOrFail();
            $total = array_sum($k->alokasi());

            $this->assertSame((float) $uang, (float) $k->jumlah_uang, "jumlah uang tersimpan salah untuk $uang");
            $this->assertLessThanOrEqual((float) $uang, $total, "Total alokasi melebihi uang untuk input $uang");
            $this->assertSame(100, array_sum($k->persenAlokasi()), "Persentase harus berjumlah 100 untuk input $uang");
        }
    }

    public function test_endpoint_lama_menolak_alokasi_yang_kosong_total(): void
    {
        $user = User::factory()->create();

        // 0/0/0 bukan pilihan masuk akal dan bukan "belum input", jadi ditolak.
        $this->actingAs($user)->post(route('keuangan.simpan'), [
            'jumlah_uang' => 600000,
            'pengeluaran_wajib' => 0,
            'target_tabungan' => 0,
            'budget_hiburan' => 0,
        ])->assertSessionHasErrors('jumlah_uang');

        $this->assertDatabaseMissing('keuangan', ['user_id' => $user->id]);
    }

    public function test_endpoint_lama_menolak_alokasi_melebihi_uang(): void
    {
        $user = User::factory()->create();

        // uang 600.000 tapi total alokasi 900.000 -> harus ditolak.
        $this->actingAs($user)->post(route('keuangan.simpan'), [
            'jumlah_uang' => 600000,
            'pengeluaran_wajib' => 600000,
            'target_tabungan' => 200000,
            'budget_hiburan' => 100000,
        ])->assertSessionHasErrors('jumlah_uang');

        $this->assertDatabaseMissing('keuangan', ['user_id' => $user->id]);
    }

    public function test_kategori_nol_tetap_valid_bila_merupakan_pilihan_eksplisit(): void
    {
        $user = User::factory()->create();

        // Tabungan 0 dan Hiburan 0 tetap sah bila totalnya tidak nol,
        // karena itu pilihan eksplisit user.
        $this->actingAs($user)->post(route('keuangan.simpan'), [
            'jumlah_uang' => 600000,
            'pengeluaran_wajib' => 600000,
            'target_tabungan' => 0,
            'budget_hiburan' => 0,
        ])->assertRedirect('/rekomendasi');

        $a = Keuangan::where('user_id', $user->id)->firstOrFail()->alokasi();
        $this->assertSame(600000.0, (float) $a['kebutuhan']);
        $this->assertSame(0.0, (float) $a['tabungan']);
        $this->assertSame(0.0, (float) $a['hiburan']);
    }

    public function test_target_harian_simpan_memperbarui_alokasi_secara_proporsional(): void
    {
        $user = User::factory()->create();
        $this->anggaran($user, 1500000, 30);

        $this->actingAs($user)->post(route('target-harian.simpan'), [
            'jumlah_uang' => 600000,
            'target_hari' => 30,
        ])->assertRedirect(route('target-harian.index'));

        $k = Keuangan::where('user_id', $user->id)->firstOrFail();
        $a = $k->alokasi();

        $this->assertSame(600000.0, (float) $k->jumlah_uang, 'jumlah_uang harus ikut berubah');
        $this->assertFalse($k->alokasiMelebihiUang(), 'Alokasi tidak boleh melebihi jumlah uang');
        $this->assertSame(600000.0, (float) array_sum($a), 'Total alokasi harus tepat sama dengan jumlah uang');

        // Perbandingan alokasi user harus dipertahankan (50/30/20 dari 50/30/20).
        $p = $k->persenAlokasi();
        $this->assertSame(50, $p['kebutuhan']);
        $this->assertSame(30, $p['tabungan']);
        $this->assertSame(20, $p['hiburan']);
    }

    // =========================================================
    // D. OWNERSHIP / IDOR
    // =========================================================

    public function test_user_tidak_bisa_membaca_transaksi_milik_user_lain(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $trx = $this->catat($a, 50000);

        $this->actingAs($b)->get(route('transaksi.edit', $trx->id))->assertNotFound();
        $this->actingAs($b)->post(route('transaksi.update', $trx->id), [
            'nama' => 'Dibajak', 'jumlah' => 1, 'jenis' => 'pengeluaran',
            'kategori_id' => $this->kategori()->id, 'tanggal' => now()->toDateString(),
        ])->assertNotFound();
        $this->actingAs($b)->delete(route('transaksi.hapus', $trx->id))->assertNotFound();

        $this->assertSame(50000.0, (float) $trx->fresh()->jumlah);
        $this->assertDatabaseHas('transaksi', ['id' => $trx->id, 'user_id' => $a->id]);
    }

    public function test_user_tidak_bisa_menulis_target_tabungan_milik_user_lain(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();

        $target = TargetTabungan::create([
            'user_id' => $a->id,
            'nama_target' => 'Milik A',
            'jumlah_target' => 1000000,
            'tabungan_saat_ini' => 0,
        ]);

        $this->actingAs($b)
            ->post(route('target-tabungan.update-saldo', $target->id), ['tambahan_saldo' => 500000])
            ->assertNotFound();
        $this->actingAs($b)->delete(route('target-tabungan.hapus', $target->id))->assertNotFound();

        $this->assertSame(0.0, (float) $target->fresh()->tabungan_saat_ini);
    }

    public function test_notifikasi_milik_user_lain_tidak_bisa_diubah_atau_dihapus(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();

        $notif = \App\Models\Notification::createNotification($a->id, 'Rahasia A', 'Isi', 'info');

        $this->actingAs($b)->post(route('notifications.read', $notif->id))->assertNotFound();
        $this->actingAs($b)->delete(route('notifications.delete', $notif->id))->assertNotFound();

        $this->assertFalse((bool) $notif->fresh()->dibaca);
        $this->assertDatabaseHas('notifications', ['id' => $notif->id, 'user_id' => $a->id]);
    }

    public function test_dan_transaksi_user_lain_tidak_ikut_di_ringkasan_halaman(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();

        $this->anggaran($a, 1000000, 30);
        $this->anggaran($b, 1000000, 30);
        $this->catat($b, 250000);

        $sisaA = DailyTargetService::hitungSisaUang(Keuangan::where('user_id', $a->id)->firstOrFail(), $a->id);
        $this->assertSame(1000000.0, (float) $sisaA, 'Transaksi user lain tidak boleh mengurangi saldo user A');

        $this->actingAs($a)->get(route('laporan.index'))
            ->assertOk()
            ->assertDontSee('Trx 250000');
    }

    public function test_admin_hanya_bisa_diakses_admin(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.users'))->assertForbidden();

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
    }

    // =========================================================
    // E. EDGE CASE NOMINAL
    // =========================================================

    public function test_nominal_kecil_tidak_menghasilkan_error(): void
    {
        foreach ([1, 9, 10000, 50000, 100000, 600000, 1000000] as $uang) {
            $user = User::factory()->create();
            $k = $this->anggaran($user, $uang, 30);

            $t = DailyTargetService::calculateDailyTarget($k, $user->id);
            $this->assertGreaterThanOrEqual(0, $t['batas_harian']);

            $this->actingAs($user)->get(route('dashboard'))->assertOk();
            $this->actingAs($user)->get(route('target-harian.index'))->assertOk();
            $this->actingAs($user)->get(route('insight.index'))->assertOk();
            $this->actingAs($user)->get(route('laporan.index'))->assertOk();
            $this->actingAs($user)->get(route('rekomendasi'))->assertOk();
        }
    }

    public function test_transaksi_berubah_tidak_membikin_halaman_rusak(): void
    {
        $user = User::factory()->create();
        $k = $this->anggaran($user, 600000, 30);

        // Skenario A: pemasukan besar
        $this->catat($user, 5000000, 'pemasukan');
        $this->actingAs($user)->get(route('dashboard'))->assertOk();

        // Skenario B: pengeluaran melebihi saldo
        $this->catat($user, 9000000, 'pengeluaran');
        $this->actingAs($user)->get(route('dashboard'))->assertOk();
        $this->actingAs($user)->get(route('target-harian.index'))->assertOk();
        $this->actingAs($user)->get(route('insight.index'))->assertOk();
        $this->actingAs($user)->get(route('laporan.index'))->assertOk();
    }

    public function test_target_tabungan_selesai_ditampilkan_dengan_benar(): void
    {
        $user = User::factory()->create();
        $target = TargetTabungan::create([
            'user_id' => $user->id,
            'nama_target' => 'Sudah tercapai',
            'jumlah_target' => 100000,
            'tabungan_saat_ini' => 100000,
        ]);

        $this->actingAs($user)->get(route('target-tabungan.index'))
            ->assertOk()
            ->assertSee('Sudah tercapai')
            ->assertSee('Selesai');
    }

    public function test_user_tanpa_keuangan_tetap_bisa_buka_semua_halaman(): void
    {
        $user = User::factory()->create();

        $this->assertDatabaseMissing('keuangan', ['user_id' => $user->id]);

        // Halaman-halaman ini tidak boleh membuat data keuangan apa pun.
        foreach ([
            'setup-anggaran.index', 'transaksi.index',
            'target-tabungan.index', 'notifications.index',
        ] as $rute) {
            $this->actingAs($user)->get(route($rute))->assertOk();
        }

        $this->assertDatabaseMissing('keuangan', ['user_id' => $user->id]);
    }

    /**
     * Membuka Dashboard tidak boleh menulis baris keuangan fiktif untuk user
     * yang belum pernah mengatur anggaran.
     */
    public function test_dashboard_tidak_menulis_anggaran_fiktif_untuk_user_yang_belum_setup(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Atur keuanganmu terlebih dahulu')
            ->assertDontSee('Rp600.000')
            ->assertDontSee('Rp400.000')
            ->assertDontSee('Rp300.000');

        $this->assertDatabaseMissing('keuangan', ['user_id' => $user->id]);
    }

    /**
     * Hanya ada satu definisi saldo, dan target tabungan tidak memotong saldo.
     */
    public function test_target_tabungan_tidak_otomatis_mengurangi_saldo(): void
    {
        $user = User::factory()->create();
        $k = $this->anggaran($user, 1000000, 30);

        TargetTabungan::create([
            'user_id' => $user->id,
            'nama_target' => 'Laptop',
            'jumlah_target' => 500000,
            'tabungan_saat_ini' => 300000,
        ]);

        $t = DailyTargetService::calculateDailyTarget($k, $user->id);

        $this->assertSame(1000000.0, (float) $t['saldo_aktual']);
        $this->assertSame(1000000.0, (float) $t['saldo_tersedia'], 'Target tabungan tidak boleh memotong saldo');
        $this->assertSame(0.0, (float) $t['uang_disisihkan']);
    }

    /**
     * Uang yang benar-benar disisihkan HARUS mengurangi uang yang tersedia.
     */
    public function test_uang_disisihkan_mengurangi_uang_tersedia(): void
    {
        $user = User::factory()->create();
        $k = $this->anggaran($user, 1000000, 30);
        $k->update(['uang_disisihkan' => 300000]);

        $t = DailyTargetService::calculateDailyTarget($k->fresh(), $user->id);

        $this->assertSame(1000000.0, (float) $t['saldo_aktual'], 'Saldo aktual tidak dipengaruhi penyisihan');
        $this->assertSame(300000.0, (float) $t['uang_disisihkan']);
        $this->assertSame(700000.0, (float) $t['saldo_tersedia'], 'Uang tersedia harus berkurang Rp300.000');
    }
}