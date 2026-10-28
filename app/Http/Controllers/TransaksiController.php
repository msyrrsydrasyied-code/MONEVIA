<?php

namespace App\Http\Controllers;

use App\Models\Transaksi;
use App\Models\Kategori;
use App\Models\Keuangan;
use App\Models\Notification;
use App\Services\DailyTargetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TransaksiController extends Controller
{
    // 1. Menampilkan semua transaksi milik user login dengan fitur search & filter (Stage 5)
    public function index(Request $request)
    {
        $userId = Auth::id();

        // Base Query dikunci dengan data ownership user login
        $query = Transaksi::where('user_id', $userId)->with('kategori');

        // Fitur Pencarian (Nama & Keterangan)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('keterangan', 'like', "%{$search}%");
            });
        }

        // Fitur Filter Jenis Transaksi (pemasukan / pengeluaran)
        if ($request->filled('jenis') && $request->jenis != 'semua') {
            $query->where('jenis', $request->jenis);
        }

        // Fitur Filter Kategori
        if ($request->filled('kategori_id') && $request->kategori_id != 'semua') {
            $query->where('kategori_id', $request->kategori_id);
        }

        // Fitur Filter Tanggal
        if ($request->filled('filter_tanggal') && $request->filter_tanggal != 'semua') {
            if ($request->filter_tanggal == 'hari_ini') {
                $query->whereDate('tanggal', Carbon::today());
            } elseif ($request->filter_tanggal == 'minggu_ini') {
                $query->whereBetween('tanggal', [Carbon::now()->startOfWeek()->format('Y-m-d'), Carbon::now()->endOfWeek()->format('Y-m-d')]);
            } elseif ($request->filter_tanggal == 'bulan_ini') {
                $query->whereMonth('tanggal', Carbon::now()->month)
                      ->whereYear('tanggal', Carbon::now()->year);
            } elseif ($request->filter_tanggal == 'custom' && $request->filled('dari_tanggal') && $request->filled('sampai_tanggal')) {
                $query->whereBetween('tanggal', [$request->dari_tanggal, $request->sampai_tanggal]);
            }
        }

        // Hitung statistik ringkasan total khusus HASIL FILTER aktif (sebelum pagination)
        $filteredTransactions = (clone $query)->get();
        $totalPemasukan = $filteredTransactions->where('jenis', 'pemasukan')->sum('jumlah');
        $totalPengeluaran = $filteredTransactions->where('jenis', 'pengeluaran')->sum('jumlah');
        $totalTransaksi = $filteredTransactions->count();

        // Ambil data transaksi terurut dengan pagination 10 item per halaman & simpan query string
        $transaksi = $query->orderBy('tanggal', 'desc')
                           ->orderBy('created_at', 'desc')
                           ->paginate(10)
                           ->withQueryString();

        // Ambil daftar semua kategori dari database untuk pilihan dropdown filter
        $kategori = Kategori::all();

        // Mengirim data ke view transaksi/index.blade.php
        return view('transaksi.index', compact(
            'transaksi',
            'kategori',
            'totalPemasukan',
            'totalPengeluaran',
            'totalTransaksi'
        ));
    }

    // 2. Menampilkan form tambah transaksi
    public function tambah()
    {
        $kategori = Kategori::all();
        return view('transaksi.tambah', compact('kategori'));
    }

    // 3. Menyimpan data transaksi baru ke database dengan user_id milik user login
    public function simpan(Request $request)
    {
        $request->validate([
            'nama' => 'required',
            'jumlah' => 'required|numeric|min:1',
            'jenis' => 'required|in:pemasukan,pengeluaran',
            'kategori_id' => 'required',
            'tanggal' => 'required|date',
        ]);

        $userId = Auth::id();

        $transaksi = Transaksi::create([
            'user_id' => $userId,
            'kategori_id' => $request->kategori_id,
            'nama' => $request->nama,
            'jumlah' => $request->jumlah,
            'jenis' => $request->jenis,
            'tanggal' => $request->tanggal,
            'keterangan' => $request->keterangan,
        ]);

        // Evaluasi notifikasi & target harian secara dinamis untuk user login
        $this->evaluateDailyTargetNotifications($userId, $request->jenis);

        return redirect('/transaksi')->with('sukses', 'Transaksi berhasil ditambahkan!');
    }

    // 4. Menampilkan form edit transaksi (dengan pengecekan ownership)
    public function edit($id)
    {
        $userId = Auth::id();
        $transaksi = Transaksi::where('user_id', $userId)->findOrFail($id);
        $kategori = Kategori::all();

        return view('transaksi.edit', compact('transaksi', 'kategori'));
    }

    // 5. Mengubah/Memperbarui data transaksi (dengan pengecekan ownership)
    public function update(Request $request, $id)
    {
        $request->validate([
            'nama' => 'required',
            'jumlah' => 'required|numeric|min:1',
            'jenis' => 'required|in:pemasukan,pengeluaran',
            'kategori_id' => 'required',
            'tanggal' => 'required|date',
        ]);

        $userId = Auth::id();
        $transaksi = Transaksi::where('user_id', $userId)->findOrFail($id);

        $transaksi->update([
            'kategori_id' => $request->kategori_id,
            'nama' => $request->nama,
            'jumlah' => $request->jumlah,
            'jenis' => $request->jenis,
            'tanggal' => $request->tanggal,
            'keterangan' => $request->keterangan,
        ]);

        $this->evaluateDailyTargetNotifications($userId, $request->jenis);

        return redirect('/transaksi')->with('sukses', 'Transaksi berhasil diperbarui!');
    }

    // 6. Menghapus data transaksi (dengan pengecekan ownership)
    public function hapus($id)
    {
        $userId = Auth::id();
        $transaksi = Transaksi::where('user_id', $userId)->findOrFail($id);
        $transaksi->delete();

        return redirect('/transaksi')->with('sukses', 'Transaksi berhasil dihapus!');
    }

    // Helper untuk mengevaluasi Target Harian dan membuat Notifikasi otomatis per user
    private function evaluateDailyTargetNotifications($userId, $jenis)
    {
        // Notifikasi hanya relevan saat user mencatat pengeluaran.
        if ($jenis !== 'pengeluaran') {
            return;
        }

        $keuangan = Keuangan::where('user_id', $userId)->first();
        if (!$keuangan) {
            return;
        }

        // Semua perhitungan memakai DailyTargetService, tidak diulang di controller ini.
        $targetInfo = DailyTargetService::calculateDailyTarget($keuangan, $userId);

        if (!$targetInfo['is_set'] || $targetInfo['periode_selesai']) {
            return;
        }

        $pengeluaranHariIni = Transaksi::where('user_id', $userId)
            ->where('jenis', 'pengeluaran')
            ->whereDate('tanggal', Carbon::today())
            ->sum('jumlah');

        $status = DailyTargetService::statusHarian($targetInfo, (float) $pengeluaranHariIni);

        // Kirim notifikasi hanya saat status worsened, dan maksimal sekali per hari
        // supaya user tidak dibanjiri notifikasi setiap kali menambah transaksi.
        if ($status['status'] === DailyTargetService::STATUS_PERINGATAN) {
            Notification::buatSekaliPerHari(
                $userId,
                'Mulai Mendekati Batas Harian',
                'Pengeluaranmu hari ini sudah ' . $status['persentase_pakai'] . '% dari batas harian '
                . '(Rp ' . number_format($status['batas_harian'], 0, ',', '.') . '). '
                . 'Coba observe pengeluaran yang tidak terlalu penting ya.',
                'warning'
            );
        }

        if ($status['status'] === DailyTargetService::STATUS_BOROS) {
            Notification::buatSekaliPerHari(
                $userId,
                'Pengeluaran Melebihi Batas Harian',
                'Pengeluaranmu hari ini sudah melewati batas harian ('
                . $status['persentase_pakai'] . '% dari Rp '
                . number_format($status['batas_harian'], 0, ',', '.') . '). '
                . 'Coba tahan pengeluaran tambahan supaya uang cukup sampai target selesai.',
                'danger'
            );
        }

        $sisaUang = (float) $targetInfo['sisa_uang'];
        if ($sisaUang > 0 && $sisaUang < 200000) {
            Notification::buatSekaliPerHari(
                $userId,
                'Sisa Uang Mulai Menipis',
                'Sisa uangmu tinggal Rp ' . number_format($sisaUang, 0, ',', '.') . '. '
                . 'Kurangi dulu pengeluaran yang bisa ditunda supaya target tetap tercapai.',
                'warning'
            );
        }
    }
}
