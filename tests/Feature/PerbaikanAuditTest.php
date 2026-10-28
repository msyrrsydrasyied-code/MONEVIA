<?php

namespace Tests\Feature;

use App\Models\Keuangan;
use App\Models\Kategori;
use App\Models\TargetTabungan;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\DailyTargetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Tahap 4 Audit — 12 kasus wajib yang diminta setelah perbaikan bug keuangan.
 *
 * Setiap case memverifikasi NILAI hasil perhitungan, bukan hanya HTTP 200.
 */
class PerbaikanAuditTest extends TestCase
{
    use RefreshDatabase;

    private function kategori(string $nama = 'Makan'): Kategori
    {
        return Kategori::firstOrCreate(['nama_kategori' => $nama]);
    }

    private function catat(User $user, int $jumlah, string $jenis = 'pengeluaran'): Transaksi
    {
        return Transaksi::create([
            'user_id' => $user->id,
            'kategori_id' => $this->kategori()->id,
            'nama' => 'Trx',
            'jumlah' => $jumlah,
            'jenis' => $jenis,
            'tanggal' => now()->toDateString(),
        ]);
    }

    private function aturAnggaran(User $user, int $uang, array $persen = [50, 30, 20], int $periode = 30): Keuangan
    {
        $response = $this->actingAs($user)->post(route('setup-anggaran.simpan'), [
            'jumlah_uang' => (string) $uang,
            'periode_hari' => $periode,
            'persen_kebutuhan' => $persen[0],
            'persen_tabungan' => $persen[1],
            'persen_hiburan' => $persen[2],
        ]);
        $response->assertRedirect(route('dashboard'));

        return Keuangan::where('user_id', $user->id)->firstOrFail();
    }

    // CASE 1 — user belum setup keuangan: tidak ada anggaran fiktif.
    public function test_case_1_user_belum_setup_tidak_dapat_anggaran_fiktif(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Atur keuanganmu terlebih dahulu');

        $this->assertDatabaseCount('keuangan', 0);

        // Halaman lain tetap aman dan tidak menulis apa pun.
        foreach (['insight.index', 'laporan.index', 'target-harian.index', 'transaksi.index'] as $rute) {
            $this->actingAs($user)->get(route($rute))->assertOk();
        }

        $this->assertDatabaseCount('keuangan', 0);
    }

    // CASE 2 — Rp600.000: total alokasi tidak boleh melebihi Rp600.000.
    public function test_case_2_total_alokasi_tidak_melebihi_rp600rb(): void
    {
        $user = User::factory()->create();
        $k = $this->aturAnggaran($user, 600000);

        $a = $k->alokasi();
        $this->assertLessThanOrEqual(600000.0, array_sum($a));
        $this->assertFalse($k->alokasiMelebihiUang());
        $this->assertSame(300000.0, (float) $a['kebutuhan']);
        $this->assertSame(180000.0, (float) $a['tabungan']);
        $this->assertSame(120000.0, (float) $a['hiburan']);
        $this->assertSame(600000.0, (float) array_sum($a));
    }

    // CASE 3 — Rp1.000.000 dengan pengeluaran Rp70.000: saldo konsisten di semua fitur.
    public function test_case_3_saldo_konsisten_di_seluruh_halaman(): void
    {
        $user = User::factory()->create();
        $k = $this->aturAnggaran($user, 1000000);
        $this->catat($user, 70000);

        $t = DailyTargetService::calculateDailyTarget($k, $user->id);
        $this->assertSame(930000.0, (float) $t['saldo_aktual']);
        $this->assertSame(930000.0, (float) $t['saldo_tersedia']);

        // Semua halaman menampilkan angka yang sama.
        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('930.000');
        $this->actingAs($user)->get(route('laporan.index'))->assertOk()->assertSee('930.000');
        $this->actingAs($user)->get(route('insight.index'))->assertOk();
        $this->actingAs($user)->get(route('target-harian.index'))->assertOk()->assertSee('930.000');
    }

    // CASE 4 — disisihkan Rp300.000: uang yang dapat digunakan berkurang.
    public function test_case_4_uang_disisihkan_mengurangi_uang_tersedia(): void
    {
        $user = User::factory()->create();
        $k = $this->aturAnggaran($user, 1000000);

        $this->actingAs($user)->post(route('setup-anggaran.simpan'), [
            'jumlah_uang' => '1000000',
            'periode_hari' => 30,
            'persen_kebutuhan' => 50,
            'persen_tabungan' => 30,
            'persen_hiburan' => 20,
            'uang_disisihkan' => '300000',
        ])->assertRedirect(route('dashboard'));

        $k = $k->fresh();
        $t = DailyTargetService::calculateDailyTarget($k, $user->id);

        $this->assertSame(1000000.0, (float) $t['saldo_aktual']);
        $this->assertSame(300000.0, (float) $t['uang_disisihkan']);
        $this->assertSame(700000.0, (float) $t['saldo_tersedia']);

        // Batas harian ikut memakai uang yang tersedia, bukan saldo aktual.
        $this->assertSame((int) round(700000 / 30), $t['batas_harian']);

        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('700.000');
    }

    // CASE 5 — target tabungan Rp300.000 tidak boleh otomatis mengurangi saldo.
    public function test_case_5_target_tabungan_tidak_mengurangi_saldo(): void
    {
        $user = User::factory()->create();
        $k = $this->aturAnggaran($user, 1000000);

        TargetTabungan::create([
            'user_id' => $user->id,
            'nama_target' => 'Laptop',
            'jumlah_target' => 300000,
            'tabungan_saat_ini' => 0,
        ]);

        $t = DailyTargetService::calculateDailyTarget($k, $user->id);
        $this->assertSame(1000000.0, (float) $t['saldo_tersedia']);
        $this->assertSame(0.0, (float) $t['uang_disisihkan']);
    }

    // CASE 6 — saldo negatif tidak boleh berubah jadi Rp0.
    public function test_case_6_saldo_negatif_tetap_negatif(): void
    {
        $user = User::factory()->create();
        $k = $this->aturAnggaran($user, 1000000);
        $this->catat($user, 1125000);

        $t = DailyTargetService::calculateDailyTarget($k, $user->id);
        $this->assertSame(-125000.0, (float) $t['saldo_aktual']);
        $this->assertSame(-125000.0, (float) $t['saldo_tersedia']);
        $this->assertSame(-125000.0, (float) DailyTargetService::hitungSaldoAktual($k, $user->id));
    }

    // CASE 7 — saldo negatif: batas harian tidak boleh negatif.
    public function test_case_7_batas_harian_tidak_pernah_negatif(): void
    {
        $user = User::factory()->create();
        $k = $this->aturAnggaran($user, 1000000);
        $this->catat($user, 1125000);

        $t = DailyTargetService::calculateDailyTarget($k, $user->id);
        $this->assertSame(0, $t['batas_harian']);
        $this->assertGreaterThanOrEqual(0, $t['batas_harian']);

        $status = DailyTargetService::statusHarian($t, 1125000);
        $this->assertSame(DailyTargetService::STATUS_BOROS, $status['status']);

        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('125.000');
        $this->actingAs($user)->get(route('target-harian.index'))->assertOk();
    }

    // CASE 8 — Rp600.000 dengan alokasi Rp900.000: validasi harus gagal.
    public function test_case_8_alokasi_melebihi_uang_ditolak(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('keuangan.simpan'), [
            'jumlah_uang' => 600000,
            'pengeluaran_wajib' => 600000,
            'target_tabungan' => 200000,
            'budget_hiburan' => 100000,
        ])->assertSessionHasErrors('jumlah_uang');

        $this->assertDatabaseMissing('keuangan', ['user_id' => $user->id]);
    }

    // CASE 9 — halaman target harian tidak boleh error key tanggal.
    public function test_case_9_tidak_ada_undefined_array_key_pada_target_harian(): void
    {
        // Kondisi tanpa data sama sekali.
        $userKosong = User::factory()->create();
        $this->actingAs($userKosong)->get(route('target-harian.index'))->assertOk();

        // Kondisi sudah setup.
        $user = User::factory()->create();
        $this->aturAnggaran($user, 1000000);
        $this->actingAs($user)->get(route('target-harian.index'))->assertOk();

        // Bentuk targetInfo harus identik di kedua kondisi.
        $kosong = DailyTargetService::targetInfoKosong();
        $isi = DailyTargetService::calculateDailyTarget(
            Keuangan::where('user_id', $user->id)->firstOrFail(),
            $user->id
        );

        foreach ($kosong as $key => $nilai) {
            $this->assertArrayHasKey($key, $isi, "Kunci targetInfo hilang: $key");
        }
        foreach ($isi as $key => $nilai) {
            $this->assertArrayHasKey($key, $kosong, "Kunci targetInfo tidak dikenal: $key");
        }
    }

    // CASE 10 — insight tidak boleh menghasilkan rasio absurd.
    public function test_case_10_rasio_insight_tidak_absurd(): void
    {
        // Dasar denominator: alokasi kebutuhan prorata, bukan sisa uang.
        // 1.000.000 / 30 hari, kebutuhan 50% -> 16.666,67 per hari. Hari
        // pertama selalu dihitung sebagai satu hari berjalan.
        $userA = User::factory()->create();
        $kA = $this->aturAnggaran($userA, 1000000);
        $this->catat($userA, 100000);
        $tA = DailyTargetService::calculateDailyTarget($kA, $userA->id);
        $anggaranA = DailyTargetService::anggaranKebutuhanSampaiSekarang($kA, $tA);

        $this->assertEqualsWithDelta(500000 / 30, $anggaranA, 0.01, 'Anggaran kebutuhan harus prorata dari alokasi, bukan sisa uang');

        // Pengeluaran 100.000 pada hari pertama = 600% dari laju kebutuhan harian.
        // Angka ini memang tinggi dan itu wajar; yang ditolak adalah rasio
        // tak hingga, NaN, atau lompatan yang tidak punya denominator nyata.
        $rasioA = DailyTargetService::rasioKebutuhan(100000, $anggaranA);

        $this->assertNotNull($rasioA);
        $this->assertTrue(is_finite($rasioA), 'Rasio harus selalu berupa angka finit');
        $this->assertGreaterThan(100, $rasioA);
        $this->assertLessThanOrEqual(1000, $rasioA, 'Rasio tidak boleh melompat ke angka absurd');
        $this->assertEqualsWithDelta(600, $rasioA, 0.01);

        // Pengeluaran jauh di atas laju harian -> rasio besar, bukan error.
        $userB = User::factory()->create();
        $kB = $this->aturAnggaran($userB, 1000000);
        $this->catat($userB, 900000);
        $tB = DailyTargetService::calculateDailyTarget($kB, $userB->id);
        $anggaranB = DailyTargetService::anggaranKebutuhanSampaiSekarang($kB, $tB);
        $rasioB = DailyTargetService::rasioKebutuhan(900000, $anggaranB);

        $this->assertTrue(is_finite($rasioB));
        $this->assertGreaterThan(100, $rasioB);
        $this->assertEqualsWithDelta(5400, $rasioB, 0.01);

        // Anggaran kebutuhan 0 -> rasio null, bukan division by zero.
        $userC = User::factory()->create();
        $kC = $this->aturAnggaran($userC, 1000000, [0, 100, 0]);
        $tC = DailyTargetService::calculateDailyTarget($kC, $userC->id);
        $this->assertNull(DailyTargetService::rasioKebutuhan(50000, DailyTargetService::anggaranKebutuhanSampaiSekarang($kC, $tC)));

        $this->actingAs($userB)->get(route('insight.index'))->assertOk();
    }

    // CASE 11 — satu user tidak boleh punya dua baris Keuangan.
    public function test_case_11_satu_user_hanya_punya_satu_baris_keuangan(): void
    {
        $user = User::factory()->create();

        Keuangan::create([
            'user_id' => $user->id,
            'jumlah_uang' => 500000,
            'pengeluaran_wajib' => 250000,
            'target_tabungan' => 150000,
            'budget_hiburan' => 100000,
        ]);

        // Aplikasi: setup berulang tetap satu baris.
        $this->aturAnggaran($user, 800000);
        $this->assertSame(1, Keuangan::where('user_id', $user->id)->count());

        // Database: INSERT kedua untuk user yang sama harus ditolak.
        $ditolak = false;
        try {
            DB::table('keuangan')->insert([
                'user_id' => $user->id,
                'jumlah_uang' => 100,
                'pengeluaran_wajib' => 0,
                'target_tabungan' => 0,
                'budget_hiburan' => 0,
                'uang_disisihkan' => 0,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            $ditolak = str_contains(strtolower($e->getMessage()), 'unique');
        }

        $this->assertTrue($ditolak, 'UNIQUE index keuangan.user_id harus menolak duplikat');
        $this->assertSame(1, Keuangan::where('user_id', $user->id)->count());
    }

    // CASE 12 — logout hanya POST + CSRF, route GET tidak boleh ada.
    public function test_case_12_logout_hanya_post_dengan_csrf(): void
    {
        $logoutRoutes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => $route->uri() === 'logout')
            ->map(fn ($route) => implode('|', $route->methods()))
            ->values();

        $this->assertCount(1, $logoutRoutes, 'Route logout harus tepat satu');
        $this->assertSame(['POST'], explode('|', $logoutRoutes->first()));

        $user = User::factory()->create();
        $this->actingAs($user)->get('/logout')->assertStatus(405);

        $this->actingAs($user)->post(route('logout'))->assertRedirect();
        $this->assertGuest();

        // Form logout di layout wajib memakai POST + CSRF.
        $html = $this->actingAs(User::factory()->create())->get(route('dashboard'))->getContent();
        $this->assertStringContainsString('action="http://127.0.0.1:8000/logout" method="POST"', $html);
        $this->assertStringContainsString('_token', $html);
    }
}