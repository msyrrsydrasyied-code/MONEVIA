<?php

namespace App\Http\Controllers;

use App\Models\Transaksi;
use App\Models\Kategori;
use App\Models\Keuangan;
use App\Models\TargetTabungan;
use App\Services\DailyTargetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class LaporanController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();

        // Validasi jika rentang tanggal custom diisi
        if ($request->filled('dari_tanggal') && $request->filled('sampai_tanggal')) {
            if ($request->dari_tanggal > $request->sampai_tanggal) {
                return back()->withInput()->withErrors([
                    'dari_tanggal' => 'Tanggal awal (dari) tidak boleh lebih besar dari tanggal akhir (sampai).'
                ]);
            }
        }

        // Default periode = bulan_ini jika tidak diset
        $periode = $request->get('periode', 'bulan_ini');
        $dariTanggal = $request->get('dari_tanggal');
        $sampaiTanggal = $request->get('sampai_tanggal');
        $jenisFilter = $request->get('jenis', 'semua');
        $kategoriFilter = $request->get('kategori_id', 'semua');

        // Base Query dikunci untuk user login (Multi-user ownership)
        $query = Transaksi::where('user_id', $userId)->with('kategori');

        // Filter Periode Tanggal
        $labelPeriode = 'Bulan Ini (' . Carbon::now()->translatedFormat('F Y') . ')';

        if ($periode == 'hari_ini') {
            $query->whereDate('tanggal', Carbon::today());
            $labelPeriode = 'Hari Ini (' . Carbon::today()->format('d M Y') . ')';
        } elseif ($periode == 'minggu_ini') {
            $start = Carbon::now()->startOfWeek();
            $end = Carbon::now()->endOfWeek();
            $query->whereBetween('tanggal', [$start->format('Y-m-d'), $end->format('Y-m-d')]);
            $labelPeriode = 'Minggu Ini (' . $start->format('d M Y') . ' - ' . $end->format('d M Y') . ')';
        } elseif ($periode == 'bulan_ini') {
            $query->whereMonth('tanggal', Carbon::now()->month)
                  ->whereYear('tanggal', Carbon::now()->year);
            $labelPeriode = 'Bulan Ini (' . Carbon::now()->translatedFormat('F Y') . ')';
        } elseif ($periode == 'tahun_ini') {
            $query->whereYear('tanggal', Carbon::now()->year);
            $labelPeriode = 'Tahun Ini (' . Carbon::now()->year . ')';
        } elseif ($periode == 'custom' && $dariTanggal && $sampaiTanggal) {
            $query->whereBetween('tanggal', [$dariTanggal, $sampaiTanggal]);
            $labelPeriode = 'Periode ' . date('d M Y', strtotime($dariTanggal)) . ' s/d ' . date('d M Y', strtotime($sampaiTanggal));
        }

        // Filter Jenis tambahan jika dipilih
        if ($jenisFilter && $jenisFilter != 'semua') {
            $query->where('jenis', $jenisFilter);
        }

        // Filter Kategori tambahan jika dipilih
        if ($kategoriFilter && $kategoriFilter != 'semua') {
            $query->where('kategori_id', $kategoriFilter);
        }

        // Ambil transaksi periode yang sudah terfilter
        $transaksi = $query->orderBy('tanggal', 'desc')->orderBy('created_at', 'desc')->get();

        // Kalkulasi Statistik Ringkasan Periode
        $totalPemasukan = $transaksi->where('jenis', 'pemasukan')->sum('jumlah');
        $totalPengeluaran = $transaksi->where('jenis', 'pengeluaran')->sum('jumlah');
        $jumlahPemasukan = $transaksi->where('jenis', 'pemasukan')->count();
        $jumlahPengeluaran = $transaksi->where('jenis', 'pengeluaran')->count();

        // Kondisi keuangan & total tabungan milik user login (tidak membuat data baru)
        $keuangan = Keuangan::cariUntukUser($userId);
        $totalTabungan = (float) TargetTabungan::where('user_id', $userId)->sum('tabungan_saat_ini');

        // Saldo memakai DailyTargetService supaya sama dengan Dashboard dan Insight
        $saldoReal = DailyTargetService::hitungSaldoTersedia($keuangan, $userId);

        // Breakdown Pengeluaran Per Kategori
        $kategoriPengeluaran = [];
        $transaksiPengeluaran = $transaksi->where('jenis', 'pengeluaran');

        foreach ($transaksiPengeluaran as $t) {
            $namaKat = $t->kategori ? $t->kategori->nama_kategori : 'Lain-lain';
            if (!isset($kategoriPengeluaran[$namaKat])) {
                $kategoriPengeluaran[$namaKat] = [
                    'nama' => $namaKat,
                    'total' => 0,
                    'persentase' => 0
                ];
            }
            $kategoriPengeluaran[$namaKat]['total'] += $t->jumlah;
        }

        // Hitung persentase per kategori
        if ($totalPengeluaran > 0) {
            foreach ($kategoriPengeluaran as $key => $val) {
                $kategoriPengeluaran[$key]['persentase'] = round(($val['total'] / $totalPengeluaran) * 100);
            }
        }

        // Urutkan pengeluaran kategori terbesar ke terkecil
        usort($kategoriPengeluaran, function($a, $b) {
            return $b['total'] <=> $a['total'];
        });

        // Ambil semua daftar kategori untuk dropdown
        $kategoriList = Kategori::all();

        return view('laporan.index', compact(
            'transaksi',
            'totalPemasukan',
            'totalPengeluaran',
            'jumlahPemasukan',
            'jumlahPengeluaran',
            'totalTabungan',
            'saldoReal',
            'kategoriPengeluaran',
            'kategoriList',
            'labelPeriode',
            'periode',
            'dariTanggal',
            'sampaiTanggal',
            'jenisFilter',
            'kategoriFilter'
        ));
    }
}

