<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Notification;
use App\Models\TargetTabungan;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tahap 3 — memastikan redesign UI/UX tidak merusak fitur yang sudah berjalan.
 */
class UiRedesignTest extends TestCase
{
    use RefreshDatabase;

    private function kategori(string $nama = 'Makan'): Kategori
    {
        return Kategori::firstOrCreate(['nama_kategori' => $nama]);
    }

    private function transaksi(User $user, string $nama, int $jumlah, string $jenis, ?string $tanggal = null, ?string $kategori = null): Transaksi
    {
        return Transaksi::create([
            'user_id' => $user->id,
            'kategori_id' => $this->kategori($kategori ?? 'Makan')->id,
            'nama' => $nama,
            'jumlah' => $jumlah,
            'jenis' => $jenis,
            'tanggal' => $tanggal ?? now()->toDateString(),
            'keterangan' => 'catatan ' . $nama,
        ]);
    }

    // ==================== LOGIN & REGISTER ====================

    public function test_halaman_login_dan_register_tampil_benar(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('Masuk ke Monevia');
        $this->get(route('register'))->assertOk()->assertSee('Buat akun baru');
    }

    public function test_login_dengan_data_benar_mengarah_ke_dashboard(): void
    {
        $user = User::factory()->create(['email' => 'siswa@monevia.test', 'password' => bcrypt('rahasia123')]);

        $this->post(route('login'), ['email' => 'siswa@monevia.test', 'password' => 'rahasia123'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_salah_menampilkan_error_jelas(): void
    {
        User::factory()->create(['email' => 'siswa@monevia.test', 'password' => bcrypt('rahasia123')]);

        $this->from(route('login'))
            ->post(route('login'), ['email' => 'siswa@monevia.test', 'password' => 'salah'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_register_membuat_user_dan_langsung_masuk(): void
    {
        $this->post(route('register'), [
            'name' => 'Santri Baru',
            'email' => 'baru@monevia.test',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', ['email' => 'baru@monevia.test', 'role' => 'user']);
        $this->assertAuthenticated();
    }

    public function test_register_menampilkan_error_validasi(): void
    {
        $this->from(route('register'))->post(route('register'), [
            'name' => '',
            'email' => 'bukan-email',
            'password' => '123',
            'password_confirmation' => '456',
        ])->assertSessionHasErrors(['name', 'email', 'password']);

        $this->assertGuest();
    }

    public function test_admin_diarahkan_ke_panel_admin_setelah_login(): void
    {
        User::factory()->create(['email' => 'admin@monevia.test', 'password' => bcrypt('rahasia123'), 'role' => 'admin']);

        $this->post(route('login'), ['email' => 'admin@monevia.test', 'password' => 'rahasia123'])
            ->assertRedirect('/admin');
    }

    // ==================== TRANSAKSI ====================

    public function test_crud_transaksi_lengkap(): void
    {
        $user = User::factory()->create();
        $kategori = $this->kategori();

        // Create
        $this->actingAs($user)->post(route('transaksi.simpan'), [
            'nama' => 'Uang saku',
            'jumlah' => 50000,
            'jenis' => 'pemasukan',
            'kategori_id' => $kategori->id,
            'tanggal' => now()->toDateString(),
            'keterangan' => 'Dari ortu',
        ])->assertRedirect('/transaksi')->assertSessionHas('sukses');

        $transaksi = Transaksi::where('user_id', $user->id)->firstOrFail();
        $this->assertSame(50000.0, (float) $transaksi->jumlah);

        // Read
        $this->actingAs($user)->get(route('transaksi.edit', $transaksi->id))->assertOk()->assertSee('Uang saku');

        // Update
        $this->actingAs($user)->post(route('transaksi.update', $transaksi->id), [
            'nama' => 'Uang saku bulan ini',
            'jumlah' => 75000,
            'jenis' => 'pemasukan',
            'kategori_id' => $kategori->id,
            'tanggal' => now()->toDateString(),
        ])->assertRedirect('/transaksi');

        $this->assertSame(75000.0, (float) $transaksi->fresh()->jumlah);

        // Delete
        $this->actingAs($user)->delete(route('transaksi.hapus', $transaksi->id))->assertRedirect('/transaksi');
        $this->assertDatabaseMissing('transaksi', ['id' => $transaksi->id]);
    }

    public function test_pencarian_transaksi(): void
    {
        $user = User::factory()->create();
        $this->transaksi($user, 'Beli buku', 40000, 'pengeluaran');
        $this->transaksi($user, 'Bayar iuran', 25000, 'pengeluaran');

        $this->actingAs($user)->get(route('transaksi.index', ['search' => 'buku']))
            ->assertOk()
            ->assertSee('Beli buku')
            ->assertDontSee('Bayar iuran');
    }

    public function test_filter_jenis_transaksi(): void
    {
        $user = User::factory()->create();
        $this->transaksi($user, 'Uang masuk', 50000, 'pemasukan');
        $this->transaksi($user, 'Makan siang', 20000, 'pengeluaran');

        $response = $this->actingAs($user)->get(route('transaksi.index', ['jenis' => 'pemasukan']));

        $response->assertOk()->assertSee('Uang masuk')->assertDontSee('Makan siang');
    }

    public function test_filter_kategori_dan_periode(): void
    {
        $user = User::factory()->create();
        $makan = $this->kategori('Makan');
        $this->kategori('Transport');
        $this->transaksi($user, 'Nasi', 20000, 'pengeluaran', null, 'Makan');
        $this->transaksi($user, 'Bensin', 30000, 'pengeluaran', now()->subDays(40)->toDateString(), 'Transport');

        $this->actingAs($user)->get(route('transaksi.index', ['kategori_id' => $makan->id]))
            ->assertOk()->assertSee('Nasi')->assertDontSee('Bensin');

        $this->actingAs($user)->get(route('transaksi.index', ['filter_tanggal' => 'bulan_ini']))
            ->assertOk()->assertSee('Nasi')->assertDontSee('Bensin');
    }

    public function test_pagination_transaksi_berfungsi(): void
    {
        $user = User::factory()->create();
        // Tanggal berbeda supaya urutan paginasi deterministik
        for ($i = 1; $i <= 15; $i++) {
            $this->transaksi($user, 'Transaksi ' . $i, 1000 * $i, 'pengeluaran', now()->subDays($i)->toDateString());
        }

        $halamanSatu = $this->actingAs($user)->get(route('transaksi.index'));
        $halamanSatu->assertOk();
        $halamanSatu->assertSee('monevia-pagination');
        $halamanSatu->assertSee('Menampilkan 1–10 dari 15 transaksi');
        $halamanSatu->assertSee('Transaksi 1');
        $halamanSatu->assertDontSee('Transaksi 15');

        $halamanDua = $this->actingAs($user)->get(route('transaksi.index', ['page' => 2]));
        $halamanDua->assertOk();
        $halamanDua->assertSee('Menampilkan 11–15 dari 15 transaksi');
        $halamanDua->assertSee('Transaksi 15');
    }

    public function test_empty_state_transaksi_tampil(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('transaksi.index'))
            ->assertOk()
            ->assertSee('Transaksi tidak ditemukan')
            ->assertSee('Mulai catat uang masuk atau keluarmu');
    }

    public function test_validasi_transaksi_belum_lengkap(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('transaksi.simpan'), ['nama' => '', 'jumlah' => 0, 'jenis' => 'ngawur'])
            ->assertSessionHasErrors(['nama', 'jumlah', 'jenis', 'kategori_id', 'tanggal']);
    }

    // ==================== TARGET TABUNGAN ====================

    public function test_crud_target_tabungan(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('target-tabungan.simpan'), [
            'nama_target' => 'Beli laptop',
            'jumlah_target' => 5000000,
            'tabungan_saat_ini' => 1000000,
            'target_tanggal' => now()->addMonths(6)->toDateString(),
        ])->assertRedirect('/target-tabungan');

        $target = TargetTabungan::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('Beli laptop', $target->nama_target);

        $this->actingAs($user)->get(route('target-tabungan.index'))
            ->assertOk()
            ->assertSee('Beli laptop')
            ->assertSee('5.000.000')
            ->assertSee('Progres terkumpul');

        // Tambah saldo
        $this->actingAs($user)->post(route('target-tabungan.update-saldo', $target->id), ['tambahan_saldo' => 500000])
            ->assertRedirect('/target-tabungan');
        $this->assertSame(1500000.0, (float) $target->fresh()->tabungan_saat_ini);

        // Saldo tidak boleh melebihi target
        $this->actingAs($user)->post(route('target-tabungan.update-saldo', $target->id), ['tambahan_saldo' => 99999999])
            ->assertSessionHas('error');
        $this->assertSame(1500000.0, (float) $target->fresh()->tabungan_saat_ini);

        // Hapus
        $this->actingAs($user)->delete(route('target-tabungan.hapus', $target->id))->assertRedirect('/target-tabungan');
        $this->assertDatabaseMissing('target_tabungan', ['id' => $target->id]);
    }

    public function test_empty_state_target_tabungan_tampil(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('target-tabungan.index'))
            ->assertOk()
            ->assertSee('Belum ada target tabungan')
            ->assertSee('Buat target pertama');

        $this->actingAs($user)->get(route('target-tabungan.tambah'))
            ->assertOk()
            ->assertSee('Tambah target tabungan');
    }

    public function test_target_tabungan_tidak_boleh_negatif(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('target-tabungan.simpan'), [
            'nama_target' => 'Kos',
            'jumlah_target' => -5,
        ])->assertSessionHasErrors('jumlah_target');
    }

    // ==================== NOTIFIKASI ====================

    public function test_notifikasi_tampil_bedakan_dibaca_dan_belum(): void
    {
        $user = User::factory()->create();
        $belum = Notification::createNotification($user->id, 'Belasan.Reminder here', 'Isi pesan', 'warning');
        $sudah = Notification::createNotification($user->id, 'Notif Lama', 'Isi lama', 'success');
        $sudah->markAsRead();

        $this->actingAs($user)->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Belasan.Reminder here')
            ->assertSee('Baru')
            ->assertSee('Warning')
            ->assertSee('Success');
    }

    public function test_tandai_dibaca_dan_hapus_notifikasi(): void
    {
        $user = User::factory()->create();
        $a = Notification::createNotification($user->id, 'Notif A', 'Isi A', 'info');
        $b = Notification::createNotification($user->id, 'Notif B', 'Isi B', 'info');

        $this->actingAs($user)->post(route('notifications.read', $a->id))->assertRedirect();
        $this->assertTrue((bool) $a->fresh()->dibaca);

        $this->actingAs($user)->post(route('notifications.read-all'))->assertRedirect();
        $this->assertTrue((bool) $b->fresh()->dibaca);

        $this->actingAs($user)->delete(route('notifications.delete', $a->id))->assertRedirect();
        $this->assertDatabaseMissing('notifications', ['id' => $a->id]);
    }

    public function test_empty_state_notifikasi_tampil(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Belum ada notifikasi');
    }

    // ==================== LAPORAN ====================

    public function test_laporan_menampilkan_ringkasan_dan_filter(): void
    {
        $user = User::factory()->create();
        $this->transaksi($user, 'Uang masuk', 100000, 'pemasukan');
        $this->transaksi($user, 'Belanja', 30000, 'pengeluaran');

        $this->actingAs($user)->get(route('laporan.index'))
            ->assertOk()
            ->assertSee('Total pemasukan')
            ->assertSee('Total pengeluaran')
            ->assertSee('Sisa uang')
            ->assertSee('Periode laporan')
            ->assertSee('Pengeluaran per kategori')
            ->assertSee('Uang masuk')
            ->assertSee('Belanja');
    }

    public function test_laporan_menyembunyikan_navigasi_dan_menampilkan_header_cetak(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('laporan.index'));

        // Konten cetak memakai kelas print-only, navigasi memakai no-print
        $response->assertSee('print-only', false);
        $response->assertSee('no-print', false);
        $response->assertSee('MONEVIA');
    }

    public function test_filter_laporan_tidak_broken(): void
    {
        $user = User::factory()->create();
        $this->transaksi($user, 'Hari ini', 20000, 'pengeluaran');
        $this->transaksi($user, 'Bulan lalu', 90000, 'pengeluaran', now()->subMonths(2)->toDateString());

        $this->actingAs($user)->get(route('laporan.index', ['periode' => 'hari_ini']))
            ->assertOk()->assertSee('Hari ini')->assertDontSee('Bulan lalu');

        $this->actingAs($user)->get(route('laporan.index', ['periode' => 'custom', 'dari_tanggal' => now()->subYear()->toDateString(), 'sampai_tanggal' => now()->toDateString()]))
            ->assertOk()->assertSee('Bulan lalu');

        // Tanggal terbalik harus ditolak dengan pesan jelas
        $this->actingAs($user)->from(route('laporan.index'))->get(route('laporan.index', [
            'periode' => 'custom',
            'dari_tanggal' => now()->toDateString(),
            'sampai_tanggal' => now()->subYear()->toDateString(),
        ]))->assertSessionHasErrors('dari_tanggal');
    }

    // ==================== INSIGHT ====================

    public function test_insight_dan_kosakata_status_konsisten(): void
    {
        $user = User::factory()->create();
        $this->transaksi($user, 'Makan', 45000, 'pengeluaran');

        $response = $this->actingAs($user)->get(route('insight.index'));
        $response->assertOk();
        $response->assertSee('Saran Money Coach');
        $response->assertSee('Analisis Pengeluaran Bulan Ini');

        // "Today" tidak lagi dipakai di seluruh UI
        $response->assertDontSee('Pengeluaran Today');
    }

    // ==================== KONSISTENSI DESIGN SYSTEM ====================

    public function test_semua_halaman_memakai_komponen_design_system(): void
    {
        $user = User::factory()->create();
        $this->transaksi($user, 'Sudah ada data', 20000, 'pengeluaran');
        TargetTabungan::create([
            'user_id' => $user->id,
            'nama_target' => 'Target Uji',
            'jumlah_target' => 1000000,
            'tabungan_saat_ini' => 250000,
        ]);
        Notification::createNotification($user->id, 'Notif Uji', 'Isi notif uji', 'info');

        $halaman = [
            'transaksi.index' => 'metric',
            'insight.index' => 'insight-item',
            'laporan.index' => 'table-card',
            'target-tabungan.index' => 'goal-card',
            'notifications.index' => 'notif-item',
            'setup-anggaran.index' => 'step-head',
        ];

        foreach ($halaman as $route => $komponen) {
            $this->actingAs($user)->get(route($route))
                ->assertOk()
                ->assertSee($komponen, false);
        }
    }

    public function test_tabel_memakai_wrapper_anti_overflow(): void
    {
        $user = User::factory()->create();
        $this->transaksi($user, 'Cek overflow', 20000, 'pengeluaran');

        foreach (['transaksi.index', 'laporan.index'] as $route) {
            $this->actingAs($user)->get(route($route))->assertOk()->assertSee('table-card-body', false);
        }
    }

    public function test_halaman_terhapus_ada_konfirmasi(): void
    {
        $user = User::factory()->create();
        $this->transaksi($user, 'Konfirmasi', 20000, 'pengeluaran');
        TargetTabungan::create([
            'user_id' => $user->id,
            'nama_target' => 'Konfirmasi Target',
            'jumlah_target' => 100000,
            'tabungan_saat_ini' => 0,
        ]);

        $this->actingAs($user)->get(route('transaksi.index'))->assertSee('confirm(', false);
        $this->actingAs($user)->get(route('target-tabungan.index'))->assertSee('confirm(', false);
    }

    public function test_halaman_admin_masih_terlindungi(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/admin')->assertForbidden();
        $this->actingAs($user)->get(route('admin.users'))->assertForbidden();

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
    }

    public function test_halaman_admin_tidak_terdampak_redesign(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        foreach (['admin.dashboard', 'admin.users', 'admin.kategori', 'admin.transaksi'] as $route) {
            $this->actingAs($admin)->get(route($route))->assertOk();
        }
    }
}
