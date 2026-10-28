<?php

namespace App\Http\Controllers;

use App\Models\Keuangan;
use App\Models\Transaksi;
use App\Models\TargetTabungan;
use App\Models\Notification;
use App\Services\DailyTargetService;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        // Data keuangan milik user login. Tidak lagi memakai firstOrCreate:
        // user yang belum setup TIDAK boleh mendapat baris anggaran fiktif.
        $keuangan = Keuangan::cariUntukUser($userId);
        $sudahSetup = $keuangan !== null && $keuangan->sudahDiset();

        // Semua angka saldo & batas harian berasal dari DailyTargetService.
        $targetHarian = DailyTargetService::calculateDailyTarget($keuangan, $userId);

        $ringkasan = DailyTargetService::ringkasanTransaksi($userId);
        $totalPemasukan = $ringkasan['total_pemasukan'];
        $totalPengeluaran = $ringkasan['total_pengeluaran'];
        $totalTabungan = (float) TargetTabungan::where('user_id', $userId)->sum('tabungan_saat_ini');

        // Satu definisi saldo saja. Tidak ada lagi rumus saldo di controller.
        $saldoAktual = $targetHarian['saldo_aktual'];
        $saldoTersedia = $targetHarian['saldo_tersedia'];
        $uangDisisihkan = $targetHarian['uang_disisihkan'];

        // Pembagian anggaran hanya ditampilkan bila user benar-benar sudah
        // menyetionkannya, supaya tidak pernah muncul plan palsu.
        $alokasi = $sudahSetup ? $keuangan->alokasi() : Keuangan::nominalDariPersen(0, Keuangan::PERSEN_BASELINE);
        $persenAlokasi = $sudahSetup ? $keuangan->persenAlokasi() : Keuangan::PERSEN_BASELINE;

        $pengeluaranHariIni = (float) Transaksi::where('user_id', $userId)
            ->where('jenis', 'pengeluaran')
            ->whereDate('tanggal', Carbon::today())
            ->sum('jumlah');

        $statusHarian = DailyTargetService::statusHarian($targetHarian, $pengeluaranHariIni);
        $status = $statusHarian['status'];
        $pesanCoach = DailyTargetService::pesanMoneyCoach($status);

        $transaksiTerakhir = Transaksi::where('user_id', $userId)
            ->with('kategori')
            ->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return view('dashboard', compact(
            'keuangan',
            'sudahSetup',
            'saldoAktual',
            'saldoTersedia',
            'uangDisisihkan',
            'totalPemasukan',
            'totalPengeluaran',
            'totalTabungan',
            'alokasi',
            'persenAlokasi',
            'status',
            'pesanCoach',
            'transaksiTerakhir',
            'targetHarian',
            'pengeluaranHariIni',
            'statusHarian'
        ));
    }
}