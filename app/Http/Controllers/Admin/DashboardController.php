<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TargetTabungan;
use App\Models\Transaksi;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $totalPengguna = User::where('role', 'user')->count();
        $totalAdmin = User::where('role', 'admin')->count();
        $totalTransaksi = Transaksi::count();
        $totalPemasukan = Transaksi::where('jenis', 'pemasukan')->sum('jumlah');
        $totalPengeluaran = Transaksi::where('jenis', 'pengeluaran')->sum('jumlah');
        $totalTabungan = TargetTabungan::sum('tabungan_saat_ini');
        $penggunaBaru = User::latest()->take(6)->get();
        $transaksiTerakhir = Transaksi::with(['kategori', 'user'])->latest()->take(8)->get();

        return view('admin.dashboard', compact(
            'totalPengguna',
            'totalAdmin',
            'totalTransaksi',
            'totalPemasukan',
            'totalPengeluaran',
            'totalTabungan',
            'penggunaBaru',
            'transaksiTerakhir'
        ));
    }
}
