<?php

namespace App\Http\Controllers;

use App\Models\Keuangan;
use App\Models\Transaksi;
use App\Services\DailyTargetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class KeuanganController extends Controller
{
    /**
     * Halaman input kondisi keuangan.
     * Alur utama sudah digabung ke Setup Anggaran Terpadu.
     */
    public function index()
    {
        return redirect()->route('setup-anggaran.index');
    }

    /**
     * Menyimpan kondisi keuangan user login.
     *
     * Invariant Monevia:
     *   total (pengeluaran_wajib + target_tabungan + budget_hiburan)
     *   tidak boleh melebihi jumlah_uang.
     *
     * Alokasi kosong total (0/0/0) ditolak karena bukan pilihan yang masuk akal,
     * sedangkan satu kategori bernilai 0 tetap sah karena itu pilihan eksplisit
     * user.
     */
    public function simpan(Request $request)
    {
        $request->validate([
            'jumlah_uang' => 'required|numeric|min:0',
            'pengeluaran_wajib' => 'required|numeric|min:0',
            'target_tabungan' => 'required|numeric|min:0',
            'budget_hiburan' => 'required|numeric|min:0',
            'uang_disisihkan' => 'nullable|numeric|min:0',
        ], [
            'jumlah_uang.required' => 'Jumlah uang wajib diisi.',
            'jumlah_uang.numeric' => 'Jumlah uang harus berupa angka.',
            'jumlah_uang.min' => 'Jumlah uang tidak boleh bernilai negatif.',
            'pengeluaran_wajib.min' => 'Pengeluaran wajib tidak boleh bernilai negatif.',
            'target_tabungan.min' => 'Target tabungan tidak boleh bernilai negatif.',
            'budget_hiburan.min' => 'Budget hiburan tidak boleh bernilai negatif.',
            'uang_disisihkan.min' => 'Uang yang disisihkan tidak boleh bernilai negatif.',
        ]);

        $jumlahUang = (float) $request->jumlah_uang;
        $kebutuhan = (float) $request->pengeluaran_wajib;
        $tabungan = (float) $request->target_tabungan;
        $hiburan = (float) $request->budget_hiburan;
        $disisihkan = (float) ($request->uang_disisihkan ?? 0);

        $totalAlokasi = $kebutuhan + $tabungan + $hiburan;

        if ($totalAlokasi <= 0) {
            return back()->withInput()->withErrors([
                'jumlah_uang' => 'Pembagian uang tidak boleh kosong semua. Isi minimal satu kategori, misalnya Kebutuhan.'
            ]);
        }

        if ($totalAlokasi > $jumlahUang) {
            return back()->withInput()->withErrors([
                'jumlah_uang' => 'Total pembagian (Kebutuhan + Tabungan + Hiburan = Rp ' . number_format($totalAlokasi, 0, ',', '.')
                    . ') tidak boleh melebihi jumlah uang yang tersedia (Rp ' . number_format($jumlahUang, 0, ',', '.') . ').'
            ]);
        }

        if ($disisihkan > $jumlahUang) {
            return back()->withInput()->withErrors([
                'uang_disisihkan' => 'Uang yang disisihkan (Rp ' . number_format($disisihkan, 0, ',', '.')
                    . ') tidak boleh melebihi jumlah uang yang tersedia (Rp ' . number_format($jumlahUang, 0, ',', '.') . ').'
            ]);
        }

        $userId = Auth::id();

        $sebelumnya = Keuangan::cariUntukUser($userId);

        $keuangan = $sebelumnya ?: new Keuangan(['user_id' => $userId]);
        $keuangan->jumlah_uang = $jumlahUang;
        $keuangan->pengeluaran_wajib = $kebutuhan;
        $keuangan->target_tabungan = $tabungan;
        $keuangan->budget_hiburan = $hiburan;
        $keuangan->uang_disisihkan = $disisihkan;
        $keuangan->save();

        return redirect('/rekomendasi')->with('sukses', 'Data kondisi keuangan berhasil disimpan!');
    }

    /**
     * Halaman hasil rekomendasi keuangan user login.
     */
    public function rekomendasi()
    {
        $userId = Auth::id();
        $keuangan = Keuangan::cariUntukUser($userId);

        if (!$keuangan || !$keuangan->sudahDiset()) {
            return redirect()->route('setup-anggaran.index');
        }

        $totalUang = (float) $keuangan->jumlah_uang;

        // Pembagian anggaran memakai nilai yang sudah disimpan user,
        // sama persis dengan Setup Anggaran, Dashboard, dan Insight.
        $alokasi = $keuangan->alokasi();
        $persenAlokasi = $keuangan->persenAlokasi();

        $kebutuhan = $alokasi['kebutuhan'];
        $tabungan = $alokasi['tabungan'];
        $fun = $alokasi['hiburan'];

        $kebutuhanPersen = $persenAlokasi['kebutuhan'];
        $tabunganPersen = $persenAlokasi['tabungan'];
        $funPersen = $persenAlokasi['hiburan'];

        // Nilai standar 50/30/20 dipakai hanya sebagai pembanding, bukan sebagai sumber angka.
        $kebutuhanIdeal = (int) round($totalUang * 0.5);
        $tabunganIdeal = (int) round($totalUang * 0.3);
        $funIdeal = (int) round($totalUang * 0.2);

        $totalAlokasi = $kebutuhan + $tabungan + $fun;

        // Status dan pesan Money Coach memakai kosakata yang sama dengan halaman lain.
        $targetInfo = DailyTargetService::calculateDailyTarget($keuangan, $userId);
        $pengeluaranHariIni = (float) Transaksi::where('user_id', $userId)
            ->where('jenis', 'pengeluaran')
            ->whereDate('tanggal', Carbon::today())
            ->sum('jumlah');

        $statusInfo = DailyTargetService::statusHarian($targetInfo, $pengeluaranHariIni);
        $status = $statusInfo['status'];
        $statusLabel = $statusInfo['label'];
        $pesanCoach = DailyTargetService::pesanMoneyCoach($status);

        return view('keuangan.rekomendasi', compact(
            'keuangan',
            'totalUang',
            'kebutuhan',
            'kebutuhanIdeal',
            'kebutuhanPersen',
            'tabungan',
            'tabunganIdeal',
            'tabunganPersen',
            'fun',
            'funIdeal',
            'funPersen',
            'totalAlokasi',
            'status',
            'statusLabel',
            'pesanCoach'
        ));
    }
}