<?php

namespace Tests\Feature;

use App\Models\Keuangan;
use App\Models\Kategori;
use App\Models\Notification;
use App\Models\TargetTabungan;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\DailyTargetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Alur Tahap 2: Login -> Setup Anggaran -> Dashboard -> Target Harian -> Transaksi -> Notifikasi
 */
class BudgetFlowTest extends TestCase
{
    use RefreshDatabase;

    private function kategori(): Kategori
    {
        return Kategori::firstOrCreate(['nama_kategori' => 'Makan']);
    }

    private function buatTransaksi(User $user, int $jumlah, string $jenis, ?string $tanggal = null): Transaksi
    {
        return Transaksi::create([
            'user_id' => $user->id,
            'kategori_id' => $this->kategori()->id,
            'nama' => 'Transaksi Uji',
            'jumlah' => $jumlah,
            'jenis' => $jenis,
            'tanggal' => $tanggal ?? now()->toDateString(),
        ]);
    }

    private function buatAnggaran(User $user, int $jumlahUang, int $periode, ?string $mulai = null): Keuangan
    {
        return Keuangan::create([
            'user_id' => $user->id,
            'jumlah_uang' => $jumlahUang,
            'pengeluaran_wajib' => (int) round($jumlahUang * 0.5),
            'target_tabungan' => (int) round($jumlahUang * 0.3),
            'budget_hiburan' => $jumlahUang - (int) round($jumlahUang * 0.5) - (int) round($jumlahUang * 0.3),
            'target_hari' => $periode,
            'tanggal_mulai_target' => $mulai ?? now()->toDateString(),
        ]);
    }

    private function catatTransaksi(User $user, int $jumlah, string $jenis = 'pengeluaran')
    {
        return $this->actingAs($user)->post(route('transaksi.simpan'), [
            'nama' => 'Belanja',
            'jumlah' => $jumlah,
            'jenis' => $jenis,
            'kategori_id' => $this->kategori()->id,
            'tanggal' => now()->toDateString(),
        ]);
    }

    // ======================= A. INPUT KEUANGAN =======================

    public function test_total_alokasi_tidak_boleh_melebihi_uang_tersedia(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('keuangan.simpan'), [
            'jumlah_uang' => 1000000,
            'pengeluaran_wajib' => 900000,
            'target_tabungan' => 900000,
            'budget_hiburan' => 900000,
        ])->assertSessionHasErrors('jumlah_uang');

        $this->assertDatabaseCount('keuangan', 0);
    }

    public function test_data_keuangan_terhubung_dengan_user_login(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $this->actingAs($userA)->post(route('setup-anggaran.simpan'), [
            'jumlah_uang' => '1000000',
            'periode_hari' => 30,
            'persen_kebutuhan' => 50,
            'persen_tabungan' => 30,
            'persen_hiburan' => 20,
        ]);

        $this->assertDatabaseMissing('keuangan', ['user_id' => $userB->id]);
        $this->assertDatabaseHas('keuangan', [
            'user_id' => $userA->id,
            'jumlah_uang' => 1000000,
            'target_hari' => 30,
        ]);
    }

    // ======================= B. DASHBOARD =======================

    public function test_dashboard_menampilkan_seluruh_ringkasan_tanpa_error(): void
    {
        $user = User::factory()->create();
        $this->buatAnggaran($user, 1000000, 30);
        $this->buatTransaksi($user, 20000, 'pemasukan');
        $this->buatTransaksi($user, 50000, 'pengeluaran');
        TargetTabungan::create([
            'user_id' => $user->id,
            'nama_target' => 'Laptop',
            'jumlah_target' => 3000000,
            'tabungan_saat_ini' => 150000,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Uang tersedia');
        $response->assertSee('Total pengeluaran');
        $response->assertSee('Total tabungan');
        $response->assertSee('Pembagian anggaranku');
        $response->assertSee('Batas belanja hari ini');
        $response->assertSee('Transaksi terakhir');
    }

    public function test_dashboard_tidak_error_saat_user_belum_punya_data(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard'))->assertOk();
    }

    public function test_dashboard_dan_setup_anggaran_menampilkan_alokasi_yang_sama(): void
    {
        $user = User::factory()->create();
        $keuangan = $this->buatAnggaran($user, 1000000, 30);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee(number_format($keuangan->pengeluaran_wajib, 0, ',', '.'));
        $response->assertSee(number_format($keuangan->target_tabungan, 0, ',', '.'));
        $response->assertSee(number_format($keuangan->budget_hiburan, 0, ',', '.'));
    }

    // ======================= C. TARGET HARIAN =======================

    public function test_batas_harian_sesuai_konsep_sisa_uang_dibagi_sisa_hari(): void
    {
        $user = User::factory()->create();
        // Mulai 10 hari lalu, target 30 hari => sisa hari 20
        $this->buatAnggaran($user, 1000000, 30, now()->subDays(10)->toDateString());
        $this->buatTransaksi($user, 200000, 'pengeluaran');

        $targetInfo = DailyTargetService::calculateDailyTarget(
            Keuangan::where('user_id', $user->id)->firstOrFail(),
            $user->id
        );

        $this->assertSame(20, $targetInfo['sisa_hari']);
        $this->assertEqualsWithDelta(800000, $targetInfo['sisa_uang'], 0.001);
        $this->assertSame(40000, $targetInfo['batas_harian']);
    }

    public function test_status_aman_saat_penggunaan_di_bawah_80_persen(): void
    {
        $user = User::factory()->create();
        $this->buatAnggaran($user, 1000000, 30);

        $this->buatTransaksi($user, 20000, 'pengeluaran');
        $targetInfo = DailyTargetService::calculateDailyTarget(Keuangan::where('user_id', $user->id)->firstOrFail(), $user->id);
        $status = DailyTargetService::statusHarian($targetInfo, 20000);

        $this->assertSame(DailyTargetService::STATUS_AMAN, $status['status']);
        $this->assertSame('Aman', $status['label']);
    }

    public function test_status_peringatan_saat_penggunaan_antara_80_dan_100_persen(): void
    {
        $user = User::factory()->create();
        $this->buatAnggaran($user, 1000000, 30);

        $targetInfo = DailyTargetService::calculateDailyTarget(Keuangan::where('user_id', $user->id)->firstOrFail(), $user->id);
        $status = DailyTargetService::statusHarian($targetInfo, 30000);

        $this->assertSame(DailyTargetService::STATUS_PERINGATAN, $status['status']);
    }

    public function test_status_boros_saat_penggunaan_melewati_batas(): void
    {
        $user = User::factory()->create();
        $this->buatAnggaran($user, 1000000, 30);
        $this->buatTransaksi($user, 40000, 'pengeluaran');

        $targetInfo = DailyTargetService::calculateDailyTarget(Keuangan::where('user_id', $user->id)->firstOrFail(), $user->id);
        $status = DailyTargetService::statusHarian($targetInfo, 40000);

        $this->assertSame(DailyTargetService::STATUS_BOROS, $status['status']);
    }

    public function test_tidak_ada_division_by_zero_saat_sisa_hari_nol(): void
    {
        $user = User::factory()->create();
        $this->buatAnggaran($user, 1000000, 30, now()->subDays(30)->toDateString());
        $this->buatTransaksi($user, 10000, 'pengeluaran');

        $targetInfo = DailyTargetService::calculateDailyTarget(Keuangan::where('user_id', $user->id)->firstOrFail(), $user->id);

        $this->assertSame(0, $targetInfo['sisa_hari']);
        $this->assertTrue($targetInfo['periode_selesai']);
        $this->assertSame(0, $targetInfo['batas_harian']);

        $status = DailyTargetService::statusHarian($targetInfo, 10000);
        $this->assertSame(DailyTargetService::STATUS_PERIODE_SELESAI, $status['status']);

        $this->actingAs($user)->get(route('target-harian.index'))->assertOk();
    }

    public function test_tidak_ada_division_by_zero_saat_sisa_uang_nol(): void
    {
        $user = User::factory()->create();
        $this->buatAnggaran($user, 1000000, 30);
        $this->buatTransaksi($user, 1000000, 'pengeluaran');

        $targetInfo = DailyTargetService::calculateDailyTarget(Keuangan::where('user_id', $user->id)->firstOrFail(), $user->id);

        $this->assertSame(0, $targetInfo['batas_harian']);
        $this->assertSame(
            DailyTargetService::STATUS_SALDO_HABIS,
            DailyTargetService::statusHarian($targetInfo, 0)['status']
        );

        $this->actingAs($user)->get(route('target-harian.index'))->assertOk();
        $this->actingAs($user)->get(route('dashboard'))->assertOk();
    }

    public function test_target_harian_aman_saat_target_belum_dibuat(): void
    {
        $user = User::factory()->create();
        $keuangan = Keuangan::create([
            'user_id' => $user->id,
            'jumlah_uang' => 1000000,
            'pengeluaran_wajib' => 500000,
            'target_tabungan' => 300000,
            'budget_hiburan' => 200000,
            'target_hari' => null,
            'tanggal_mulai_target' => null,
        ]);

        $targetInfo = DailyTargetService::calculateDailyTarget($keuangan, $user->id);

        $this->assertFalse($targetInfo['is_set']);
        $this->assertSame(0, $targetInfo['batas_harian']);
        $this->assertSame(
            DailyTargetService::STATUS_BELUM_ATUR,
            DailyTargetService::statusHarian($targetInfo, 0)['status']
        );

        $this->actingAs($user)->get(route('target-harian.index'))->assertOk();
    }

    // ======================= D. NOTIFIKASI =======================

    public function test_notifikasi_batas_harian_tidak_mengganggu_user(): void
    {
        $user = User::factory()->create();
        $this->buatAnggaran($user, 1000000, 30);

        // Lima pengeluaran melampaui batas harian
        for ($i = 0; $i < 5; $i++) {
            $this->catatTransaksi($user, 40000);
        }

        $notifikasiBoros = Notification::where('user_id', $user->id)
            ->where('judul', 'Pengeluaran Melebihi Batas Harian')
            ->count();

        $this->assertSame(1, $notifikasiBoros, 'Notifikasi boros hanya boleh dibuat sekali per hari.');
    }

    public function test_tidak_ada_notifikasi_untuk_pengeluaran_aman(): void
    {
        $user = User::factory()->create();
        $this->buatAnggaran($user, 1000000, 30);

        $this->catatTransaksi($user, 5000);

        $this->assertSame(0, Notification::where('user_id', $user->id)->count());
    }

    public function test_tidak_ada_notifikasi_saat_mencatat_pemasukan(): void
    {
        $user = User::factory()->create();
        $this->buatAnggaran($user, 1000000, 30);

        $this->catatTransaksi($user, 50000, 'pemasukan');

        $this->assertSame(0, Notification::where('user_id', $user->id)->count());
    }

    public function test_notifikasi_sisa_uang_menipis_dibuat_saat_perlu(): void
    {
        $user = User::factory()->create();
        $this->buatAnggaran($user, 1000000, 30);

        $this->catatTransaksi($user, 850000);
        $this->catatTransaksi($user, 20000);

        $this->assertGreaterThanOrEqual(
            1,
            Notification::where('user_id', $user->id)->where('judul', 'Sisa Uang Mulai Menipis')->count()
        );
    }

    // ======================= E. MONEY COACH =======================

    public function test_pesan_money_coach_bersifat_ramah_dan_tidak_menghakimi(): void
    {
        $pesan = [
            DailyTargetService::pesanMoneyCoach(DailyTargetService::STATUS_AMAN),
            DailyTargetService::pesanMoneyCoach(DailyTargetService::STATUS_PERINGATAN),
            DailyTargetService::pesanMoneyCoach(DailyTargetService::STATUS_BOROS),
        ];

        foreach ($pesan as $isi) {
            $this->assertStringNotContainsString('WOI', $isi);
            $this->assertStringNotContainsString('BOROS BANGET', $isi);
            $this->assertStringNotContainsString('!', $isi);
        }

        $this->assertStringContainsString('masih aman', $pesan[0]);
        $this->assertStringContainsString('mendekati batas', $pesan[1]);
        $this->assertStringContainsString('melewati batas', $pesan[2]);
    }

    public function test_setiap_halaman_memakai_kosakata_status_yang_sama(): void
    {
        $user = User::factory()->create();
        $this->buatAnggaran($user, 1000000, 30);

        foreach (['dashboard', 'target-harian.index', 'insight.index', 'rekomendasi'] as $rute) {
            $this->actingAs($user)->get(route($rute))->assertOk();
        }
    }

    // ======================= F. KONSISTENSI DATA =======================

    public function test_data_satu_user_tidak_bisa_dilihat_user_lain(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $this->buatAnggaran($userA, 1000000, 30);
        $this->buatTransaksi($userA, 75000, 'pengeluaran');
        TargetTabungan::create([
            'user_id' => $userA->id,
            'nama_target' => 'RahasiaA',
            'jumlah_target' => 500000,
            'tabungan_saat_ini' => 100000,
        ]);
        Notification::createNotification($userA->id, 'RahasiaA', 'PesanRahasiaA', 'info');

        $this->actingAs($userB)->get(route('dashboard'))->assertOk()->assertDontSee('RahasiaA');
        $this->actingAs($userB)->get(route('transaksi.index'))->assertOk()->assertDontSee('Transaksi Uji');
        $this->actingAs($userB)->get(route('target-tabungan.index'))->assertOk()->assertDontSee('RahasiaA');
        $this->actingAs($userB)->get(route('notifications.index'))->assertOk()->assertDontSee('PesanRahasiaA');
        $this->actingAs($userB)->get(route('laporan.index'))->assertOk()->assertDontSee('Transaksi Uji');
    }

    public function test_user_lain_tidak_bisa_mengubah_hapus_membaca_data(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $transaksiA = $this->buatTransaksi($userA, 50000, 'pengeluaran');
        $notifA = Notification::createNotification($userA->id, 'Notif A', 'Isi A', 'info');
        $targetA = TargetTabungan::create([
            'user_id' => $userA->id,
            'nama_target' => 'Target A',
            'jumlah_target' => 500000,
            'tabungan_saat_ini' => 0,
        ]);

        $this->actingAs($userB)->get(route('transaksi.edit', $transaksiA->id))->assertNotFound();
        $this->actingAs($userB)->delete(route('transaksi.hapus', $transaksiA->id))->assertNotFound();
        $this->actingAs($userB)->post(route('notifications.read', $notifA->id))->assertNotFound();
        $this->actingAs($userB)->post(route('target-tabungan.update-saldo', $targetA->id), ['tambahan_saldo' => 1000])->assertNotFound();

        $this->assertDatabaseHas('transaksi', ['id' => $transaksiA->id]);
        $this->assertFalse((bool) $notifA->fresh()->dibaca);
        $this->assertSame('0.00', number_format((float) $targetA->fresh()->tabungan_saat_ini, 2, '.', ''));
    }

    // ======================= G. ERROR HANDLING =======================

    public function test_semua_halaman_aman_dengan_data_kosong_atau_null(): void
    {
        $user = User::factory()->create();

        // /keuangan hanya redirect ke alur Setup Anggaran Terpadu
        $this->actingAs($user)->get(route('keuangan.index'))->assertRedirect(route('setup-anggaran.index'));

        $rute = [
            'dashboard',
            'setup-anggaran.index',
            'rekomendasi',
            'target-harian.index',
            'transaksi.index',
            'transaksi.tambah',
            'target-tabungan.index',
            'target-tabungan.tambah',
            'notifications.index',
            'laporan.index',
            'insight.index',
        ];

        // Rekomendasi butuh anggaran; tanpa data user diarahkan ke setup.
        $tanpaRekomendasi = array_values(array_diff($rute, ['rekomendasi']));
        foreach ($tanpaRekomendasi as $nama) {
            $this->actingAs($user)->get(route($nama))->assertOk();
        }

        $this->actingAs($user)->get(route('rekomendasi'))
            ->assertRedirect(route('setup-anggaran.index'));

        // Membuka halaman tidak boleh membuat anggaran fiktif.
        $this->assertDatabaseMissing('keuangan', ['user_id' => $user->id]);
    }

    public function test_halaman_aman_saat_keuangan_bernilai_nol(): void
    {
        $user = User::factory()->create();
        Keuangan::create([
            'user_id' => $user->id,
            'jumlah_uang' => 0,
            'pengeluaran_wajib' => 0,
            'target_tabungan' => 0,
            'budget_hiburan' => 0,
            'target_hari' => 30,
            'tanggal_mulai_target' => now()->toDateString(),
        ]);

        foreach (['dashboard', 'target-harian.index', 'insight.index', 'laporan.index'] as $nama) {
            $this->actingAs($user)->get(route($nama))->assertOk();
        }

        // Jumlah uang 0 berarti belum dianggap punya anggaran aktif.
        $this->actingAs($user)->get(route('rekomendasi'))
            ->assertRedirect(route('setup-anggaran.index'));
    }

    public function test_keuangan_tidak_dibuat_otomatis_saat_dibuka(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard'))->assertOk();

        // Dashboard tidak boleh menulis baris keuangan fiktif. Baris baru hanya
        // dibuat ketika user menjalankan aksi penyetionan anggaran.
        $this->assertDatabaseCount('keuangan', 0);
        $this->assertDatabaseMissing('keuangan', ['user_id' => $user->id]);
    }

    // ======================= ALUR LENGKAP =======================

    public function test_alur_lengkap_setup_ke_dashboard_transaksi_dan_notifikasi(): void
    {
        $user = User::factory()->create();

        // 1. Setup anggaran Rp1.000.000 selama 30 hari
        $this->actingAs($user)->post(route('setup-anggaran.simpan'), [
            'jumlah_uang' => '1000000',
            'periode_hari' => 30,
            'persen_kebutuhan' => 50,
            'persen_tabungan' => 30,
            'persen_hiburan' => 20,
        ])->assertRedirect(route('dashboard'));

        // 2. Dashboard tampil dengan batas harian 1.000.000 / 30 = 33.333
        $keuangan = Keuangan::where('user_id', $user->id)->firstOrFail();
        $targetInfo = DailyTargetService::calculateDailyTarget($keuangan, $user->id);
        $this->assertSame(30, $targetInfo['sisa_hari']);
        $this->assertSame(33333, $targetInfo['batas_harian']);
        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('33.333');

        // 3. Catat transaksi Rp20.000 (masih aman)
        $this->catatTransaksi($user, 20000);
        $this->actingAs($user)->get(route('target-harian.index'))->assertOk()->assertSee('Aman');

        // Tidak ada pengingat otomatis yang muncul saat masih aman
        $this->assertSame(0, Notification::where('user_id', $user->id)
            ->whereIn('judul', [
                'Mulai Mendekati Batas Harian',
                'Pengeluaran Melebihi Batas Harian',
                'Sisa Uang Mulai Menipis',
            ])
            ->count());

        // 4. Catat transaksi sampai melewati batas harian
        $this->catatTransaksi($user, 30000);
        $this->actingAs($user)->get(route('target-harian.index'))->assertOk()->assertSee('Boros');

        // 5. Notifikasi terbentuk
        $notifikasi = Notification::where('user_id', $user->id)
            ->where('judul', 'Pengeluaran Melebihi Batas Harian')
            ->firstOrFail();
        $this->assertStringContainsString('melewati batas harian', $notifikasi->pesan);

        // 6. Nominal transaksi yang tercatat benar
        $this->assertDatabaseHas('transaksi', ['user_id' => $user->id, 'jumlah' => 20000]);
        $this->assertDatabaseHas('transaksi', ['user_id' => $user->id, 'jumlah' => 30000]);
    }
}
