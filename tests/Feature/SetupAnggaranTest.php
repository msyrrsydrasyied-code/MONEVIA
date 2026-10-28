<?php

namespace Tests\Feature;

use App\Models\Keuangan;
use App\Models\Kategori;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\DailyTargetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetupAnggaranTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $override = []): array
    {
        return array_merge([
            'jumlah_uang' => '2000000',
            'periode_hari' => 30,
            'persen_kebutuhan' => 50,
            'persen_tabungan' => 30,
            'persen_hiburan' => 20,
        ], $override);
    }

    private function buatTransaksi(User $user, int $jumlah, string $jenis, string $tanggal): Transaksi
    {
        $kategori = Kategori::create(['nama_kategori' => 'Kategori Uji']);

        return Transaksi::create([
            'user_id' => $user->id,
            'kategori_id' => $kategori->id,
            'nama' => 'Transaksi Uji',
            'jumlah' => $jumlah,
            'jenis' => $jenis,
            'tanggal' => $tanggal,
        ]);
    }

    public function test_halaman_setup_membutuhkan_login(): void
    {
        $this->get('/setup-anggaran')->assertRedirect('/login');
    }

    public function test_halaman_setup_tidak_membuat_anggaran_baru_otomatis(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('setup-anggaran.index'))->assertOk();

        $this->assertDatabaseCount('keuangan', 0);
    }

    public function test_halaman_setup_menampilkan_anggaran_aktif(): void
    {
        $user = User::factory()->create();
        Keuangan::create([
            'user_id' => $user->id,
            'jumlah_uang' => 3000000,
            'pengeluaran_wajib' => 1500000,
            'target_tabungan' => 900000,
            'budget_hiburan' => 600000,
            'target_hari' => 60,
            'tanggal_mulai_target' => now()->subDays(5)->toDateString(),
        ]);

        $this->actingAs($user)
            ->get(route('setup-anggaran.index'))
            ->assertOk()
            ->assertSee('Anggaran Aktif')
            ->assertSee('3.000.000');
    }

    public function test_nominal_kosong_ditolak(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('setup-anggaran.simpan'), $this->payload(['jumlah_uang' => '']))
            ->assertSessionHasErrors('jumlah_uang');

        $this->assertDatabaseCount('keuangan', 0);
    }

    public function test_nominal_nol_ditolak(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('setup-anggaran.simpan'), $this->payload(['jumlah_uang' => 0]))
            ->assertSessionHasErrors('jumlah_uang');
    }

    public function test_nominal_negatif_ditolak(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('setup-anggaran.simpan'), $this->payload(['jumlah_uang' => '-500000']))
            ->assertSessionHasErrors('jumlah_uang');
    }

    public function test_nominal_lebih_dari_batas_aman_ditolak(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('setup-anggaran.simpan'), $this->payload(['jumlah_uang' => '10000000000000']))
            ->assertSessionHasErrors('jumlah_uang');
    }

    public function test_input_singkat_200k_dikonversi_ke_rupiah(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('setup-anggaran.simpan'), $this->payload(['jumlah_uang' => '200k']))
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('keuangan', [
            'user_id' => $user->id,
            'jumlah_uang' => 200000,
        ]);
    }

    public function test_total_persen_kurang_dari_100_ditolak(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('setup-anggaran.simpan'), $this->payload([
                'persen_kebutuhan' => 40,
                'persen_tabungan' => 30,
                'persen_hiburan' => 20,
            ]))
            ->assertSessionHasErrors('persen_kebutuhan');

        $this->assertDatabaseCount('keuangan', 0);
    }

    public function test_total_persen_lebih_dari_100_ditolak(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('setup-anggaran.simpan'), $this->payload([
                'persen_kebutuhan' => 60,
                'persen_tabungan' => 30,
                'persen_hiburan' => 30,
            ]))
            ->assertSessionHasErrors('persen_kebutuhan');
    }

    public function test_periode_tidak_didaftarkan_ditolak(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('setup-anggaran.simpan'), $this->payload(['periode_hari' => 45]))
            ->assertSessionHasErrors('periode_hari');
    }

    public function test_menyimpan_anggaran_menghitung_nominal_kategori(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('setup-anggaran.simpan'), $this->payload())
            ->assertRedirect(route('dashboard'));

        $keuangan = Keuangan::where('user_id', $user->id)->firstOrFail();

        $this->assertSame(2000000.0, (float) $keuangan->jumlah_uang);
        $this->assertSame(1000000.0, (float) $keuangan->pengeluaran_wajib);
        $this->assertSame(600000.0, (float) $keuangan->target_tabungan);
        $this->assertSame(400000.0, (float) $keuangan->budget_hiburan);
        $this->assertSame(30, (int) $keuangan->target_hari);
        $this->assertSame(now()->toDateString(), (string) $keuangan->tanggal_mulai_target);
    }

    public function test_total_alokasi_selalu_sama_dengan_jumlah_uang(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('setup-anggaran.simpan'), $this->payload([
            'jumlah_uang' => '333333',
            'persen_kebutuhan' => 33,
            'persen_tabungan' => 33,
            'persen_hiburan' => 34,
        ]));

        $keuangan = Keuangan::where('user_id', $user->id)->firstOrFail();
        $total = (float) $keuangan->pengeluaran_wajib
            + (float) $keuangan->target_tabungan
            + (float) $keuangan->budget_hiburan;

        $this->assertSame((float) $keuangan->jumlah_uang, $total);
    }

    public function test_memperbarui_anggaran_tidak_membuat_baris_baru(): void
    {
        $user = User::factory()->create();
        Keuangan::create([
            'user_id' => $user->id,
            'jumlah_uang' => 1000000,
            'pengeluaran_wajib' => 500000,
            'target_tabungan' => 300000,
            'budget_hiburan' => 200000,
            'target_hari' => 30,
            'tanggal_mulai_target' => now()->subDays(3)->toDateString(),
        ]);

        $this->actingAs($user)->post(route('setup-anggaran.simpan'), $this->payload([
            'jumlah_uang' => '1500k',
            'periode_hari' => 14,
        ]));

        $this->assertDatabaseCount('keuangan', 1);

        $keuangan = Keuangan::where('user_id', $user->id)->firstOrFail();
        $this->assertSame(1500000.0, (float) $keuangan->jumlah_uang);
        $this->assertSame(14, (int) $keuangan->target_hari);
        $this->assertSame(
            now()->subDays(3)->toDateString(),
            (string) $keuangan->tanggal_mulai_target,
            'Tanggal mulai lama harus dipertahankan bila pengguna tidak meminta periode baru.'
        );
    }

    public function test_mulai_ulang_mengatur_tanggal_mulai_ke_hari_ini(): void
    {
        $user = User::factory()->create();
        Keuangan::create([
            'user_id' => $user->id,
            'jumlah_uang' => 1000000,
            'pengeluaran_wajib' => 500000,
            'target_tabungan' => 300000,
            'budget_hiburan' => 200000,
            'target_hari' => 30,
            'tanggal_mulai_target' => now()->subDays(10)->toDateString(),
        ]);

        $this->actingAs($user)->post(route('setup-anggaran.simpan'), $this->payload([
            'mulai_ulang' => 1,
        ]));

        $keuangan = Keuangan::where('user_id', $user->id)->firstOrFail();

        $this->assertSame(now()->toDateString(), (string) $keuangan->tanggal_mulai_target);
    }

    public function test_transaksi_lama_tetep_aman_dan_saldo_tidak_terhitung_ganda(): void
    {
        $user = User::factory()->create();
        Keuangan::create([
            'user_id' => $user->id,
            'jumlah_uang' => 1000000,
            'pengeluaran_wajib' => 500000,
            'target_tabungan' => 300000,
            'budget_hiburan' => 200000,
            'target_hari' => 30,
            'tanggal_mulai_target' => now()->toDateString(),
        ]);
        $this->buatTransaksi($user, 100000, 'pengeluaran', now()->toDateString());
        $this->buatTransaksi($user, 50000, 'pemasukan', now()->toDateString());

        $targetInfo = DailyTargetService::calculateDailyTarget(
            Keuangan::where('user_id', $user->id)->firstOrFail(),
            $user->id
        );
        $this->assertEqualsWithDelta(950000, $targetInfo['sisa_uang'], 0.001);

        $this->actingAs($user)->post(route('setup-anggaran.simpan'), $this->payload());

        $this->assertDatabaseCount('transaksi', 2);
        $this->assertDatabaseCount('keuangan', 1);

        $keuangan = Keuangan::where('user_id', $user->id)->firstOrFail();
        $targetInfo = DailyTargetService::calculateDailyTarget($keuangan, $user->id);

        // 2.000.000 (nominal baru) + 50.000 pemasukan - 100.000 pengeluaran = 1.950.000
        $this->assertEqualsWithDelta(1950000, $targetInfo['sisa_uang'], 0.001);
    }

    public function test_data_lama_dengan_alokasi_lebih_dari_jumlah_uang_tetap_dapat_disimpan(): void
    {
        $user = User::factory()->create();
        Keuangan::create([
            'user_id' => $user->id,
            'jumlah_uang' => 600000,
            'pengeluaran_wajib' => 600000,
            'target_tabungan' => 400000,
            'budget_hiburan' => 300000,
            'target_hari' => 30,
            'tanggal_mulai_target' => now()->subDays(12)->toDateString(),
        ]);

        $this->actingAs($user)->get(route('setup-anggaran.index'))->assertOk();

        $this->actingAs($user)->post(route('setup-anggaran.simpan'), $this->payload([
            'jumlah_uang' => '600000',
        ]))->assertRedirect(route('dashboard'));

        $keuangan = Keuangan::where('user_id', $user->id)->firstOrFail();

        $this->assertSame(300000.0, (float) $keuangan->pengeluaran_wajib);
        $this->assertSame(180000.0, (float) $keuangan->target_tabungan);
        $this->assertSame(120000.0, (float) $keuangan->budget_hiburan);
    }

    public function test_data_user_lain_tidak_bisa_dilihat_maupun_diubah(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        Keuangan::create([
            'user_id' => $userA->id,
            'jumlah_uang' => 5000000,
            'pengeluaran_wajib' => 2500000,
            'target_tabungan' => 1500000,
            'budget_hiburan' => 1000000,
            'target_hari' => 90,
            'tanggal_mulai_target' => now()->toDateString(),
        ]);

        $this->actingAs($userB)
            ->get(route('setup-anggaran.index'))
            ->assertOk()
            ->assertDontSee('5.000.000');

        $this->actingAs($userB)
            ->post(route('setup-anggaran.simpan'), $this->payload())
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('keuangan', [
            'user_id' => $userB->id,
            'jumlah_uang' => 2000000,
        ]);
        $this->assertDatabaseHas('keuangan', [
            'user_id' => $userA->id,
            'jumlah_uang' => 5000000,
        ]);
    }

    public function test_endpoint_lama_keuangan_tetap_berfungsi_dan_mengarah_ke_setup(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/keuangan')->assertRedirect(route('setup-anggaran.index'));

        $this->actingAs($user)
            ->post('/keuangan/simpan', [
                'jumlah_uang' => 1200000,
                'pengeluaran_wajib' => 600000,
                'target_tabungan' => 400000,
                'budget_hiburan' => 200000,
            ])
            ->assertRedirect('/rekomendasi');

        $this->assertDatabaseHas('keuangan', [
            'user_id' => $user->id,
            'jumlah_uang' => 1200000,
        ]);
    }
}
