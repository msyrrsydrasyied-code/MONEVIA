<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TransaksiController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KeuanganController;
use App\Http\Controllers\SetupAnggaranController;
use App\Http\Controllers\TargetTabunganController;
use App\Http\Controllers\TargetHarianController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\InsightController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\KategoriController as AdminKategoriController;
use App\Http\Controllers\Admin\TransaksiController as AdminTransaksiController;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect(auth()->user()->isAdmin() ? '/admin' : '/dashboard');
    }
    return view('welcome');
});

// Route Autentikasi (Guest)
Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Logout hanya lewat POST + CSRF.
// Route GET sengaja dihapus: logout lewat URL bisa dipicu pihak lain (CSRF).
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Route Terproteksi (Membutuhkan Login / Auth Middleware)
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Setup Anggaran Terpadu (Stage 1 - alur gabungan input nominal + periode + alokasi)
    Route::get('/setup-anggaran', [SetupAnggaranController::class, 'index'])->name('setup-anggaran.index');
    Route::post('/setup-anggaran/simpan', [SetupAnggaranController::class, 'simpan'])->name('setup-anggaran.simpan');

    // Keuangan & Money Coach
    Route::get('/keuangan', [KeuanganController::class, 'index'])->name('keuangan.index');
    Route::post('/keuangan/simpan', [KeuanganController::class, 'simpan'])->name('keuangan.simpan');
    Route::get('/rekomendasi', [KeuanganController::class, 'rekomendasi'])->name('rekomendasi');

    // Target Harian (Stage 3)
    Route::get('/target-harian', [TargetHarianController::class, 'index'])->name('target-harian.index');
    Route::post('/target-harian/simpan', [TargetHarianController::class, 'simpan'])->name('target-harian.simpan');

    // Transaksi (Stage 1, Stage 4 Multi-User, & Stage 5 Filter/Search)
    Route::get('/transaksi', [TransaksiController::class, 'index'])->name('transaksi.index');
    Route::get('/transaksi/tambah', [TransaksiController::class, 'tambah'])->name('transaksi.tambah');
    Route::post('/transaksi/simpan', [TransaksiController::class, 'simpan'])->name('transaksi.simpan');
    Route::get('/transaksi/edit/{id}', [TransaksiController::class, 'edit'])->name('transaksi.edit');
    Route::post('/transaksi/update/{id}', [TransaksiController::class, 'update'])->name('transaksi.update');
    Route::delete('/transaksi/hapus/{id}', [TransaksiController::class, 'hapus'])->name('transaksi.hapus');

    // Target Tabungan (Stage 2 & Stage 4 Multi-User)
    Route::get('/target-tabungan', [TargetTabunganController::class, 'index'])->name('target-tabungan.index');
    Route::get('/target-tabungan/tambah', [TargetTabunganController::class, 'tambah'])->name('target-tabungan.tambah');
    Route::post('/target-tabungan/simpan', [TargetTabunganController::class, 'simpan'])->name('target-tabungan.simpan');
    Route::post('/target-tabungan/update-saldo/{id}', [TargetTabunganController::class, 'updateSaldo'])->name('target-tabungan.update-saldo');
    Route::delete('/target-tabungan/hapus/{id}', [TargetTabunganController::class, 'hapus'])->name('target-tabungan.hapus');

    // Notifications (Stage 3 & Stage 4 Multi-User)
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read/{id}', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::delete('/notifications/delete/{id}', [NotificationController::class, 'destroy'])->name('notifications.delete');

    // Laporan Keuangan (Stage 6)
    Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');

    // Financial Insights (Stage 7)
    Route::get('/insight', [InsightController::class, 'index'])->name('insight.index');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/pengguna', [AdminUserController::class, 'index'])->name('admin.users');
    Route::post('/pengguna', [AdminUserController::class, 'store']);
    Route::post('/pengguna/{id}/peran', [AdminUserController::class, 'updateRole']);
    Route::delete('/pengguna/{id}', [AdminUserController::class, 'destroy']);
    Route::get('/kategori', [AdminKategoriController::class, 'index'])->name('admin.kategori');
    Route::post('/kategori', [AdminKategoriController::class, 'store']);
    Route::put('/kategori/{id}', [AdminKategoriController::class, 'update']);
    Route::delete('/kategori/{id}', [AdminKategoriController::class, 'destroy']);
    Route::get('/transaksi', [AdminTransaksiController::class, 'index'])->name('admin.transaksi');
});
