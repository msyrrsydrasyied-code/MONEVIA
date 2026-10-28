<?php

namespace App\Http\Controllers;

use App\Models\Keuangan;
use App\Models\Notification;
use App\Models\Transaksi;
use App\Services\DailyTargetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TargetHarianController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        // Tidak lagi membuat baris keuangan fiktif lewat firstOrCreate.
        $keuangan = Keuangan::cariUntukUser($userId);

        // Seluruh perhitungan batas harian memakai DailyTargetService, dan
        // bentuk targetInfo selalu sama persis (sudahDiset() atau kosong).
        $targetInfo = DailyTargetService::calculateDailyTarget($keuangan, $userId);

        $pengeluaranHariIni = (float) Transaksi::where('user_id', $userId)
            ->where('jenis', 'pengeluaran')
            ->whereDate('tanggal', Carbon::today())
            ->sum('jumlah');

        $statusInfo = DailyTargetService::statusHarian($targetInfo, $pengeluaranHariIni);

        return view('target_harian.index', compact(
            'keuangan',
            'targetInfo',
            'pengeluaranHariIni',
            'statusInfo'
        ));
    }

    /**
     * Endpoint lama tetap dipertahankan untuk kompatibilitas.
     * Alur utama tetap Setup Anggaran Terpadu (/setup-anggaran).
     *
     * PENTING: ketika jumlah uang berubah, pembagian alokasi ikut menyesuaikan
     * secara proporsional. Sebelumnya alokasi dibiarkan stale sehingga totalnya
     * bisa melebihi jumlah uang (mis. uang 600.000 tapi alokasi 1.500.000).
     */
    public function simpan(Request $request)
    {
        $request->validate([
            'jumlah_uang' => 'required|numeric|min:1',
            'target_hari' => 'required|integer|min:1|max:365',
            'uang_disisihkan' => 'nullable|numeric|min:0',
        ], [
            'jumlah_uang.required' => 'Jumlah uang wajib diisi.',
            'jumlah_uang.numeric' => 'Jumlah uang harus berupa angka.',
            'jumlah_uang.min' => 'Jumlah uang minimal 1.',
            'target_hari.required' => 'Target hari wajib diisi.',
            'target_hari.integer' => 'Target hari harus berupa angka bulat.',
            'target_hari.min' => 'Target hari minimal 1 hari.',
            'target_hari.max' => 'Target hari maksimal 365 hari.',
            'uang_disisihkan.min' => 'Uang yang disisihkan tidak boleh bernilai negatif.',
        ]);

        $userId = Auth::id();
        $jumlahUang = (int) $request->jumlah_uang;
        $targetHari = (int) $request->target_hari;
        $disisihkan = (float) ($request->uang_disisihkan ?? 0);

        if ($disisihkan > $jumlahUang) {
            return back()->withInput()->withErrors([
                'uang_disisihkan' => 'Uang yang disisihkan tidak boleh melebihi jumlah uang yang tersedia.'
            ]);
        }

        $sebelumnya = Keuangan::cariUntukUser($userId);

        if ($sebelumnya && $sebelumnya->sudahDiset()) {
            // Pertahankan perbandingan alokasi user, hanya skalanya yang menyesuaikan.
            $persen = $sebelumnya->persenAlokasi();
        } else {
            // Belum pernah setup: pakai baseline 50/30/20 dari jumlah uang yang
            // baru saja dimasukkan user, bukan angka absolut.
            $persen = Keuangan::PERSEN_BASELINE;
        }

        $nominal = Keuangan::nominalDariPersen($jumlahUang, $persen);

        $keuangan = $sebelumnya ?: new Keuangan(['user_id' => $userId]);
        $keuangan->jumlah_uang = $jumlahUang;
        $keuangan->pengeluaran_wajib = $nominal['kebutuhan'];
        $keuangan->target_tabungan = $nominal['tabungan'];
        $keuangan->budget_hiburan = $nominal['hiburan'];
        $keuangan->uang_disisihkan = $disisihkan;
        $keuangan->target_hari = $targetHari;
        $keuangan->tanggal_mulai_target = Carbon::today()->toDateString();
        $keuangan->save();

        $targetInfo = DailyTargetService::calculateDailyTarget($keuangan->fresh(), $userId);

        // Notifikasi ini lahir dari aksi pengguna sendiri, bukan pengingat otomatis,
        // jadi tetap dibuat setiap kali target benar-benar disimpan.
        Notification::createNotification(
            $userId,
            'Target Harian Diperbarui',
            'Target harian kamu: Rp ' . number_format($targetInfo['batas_harian'], 0, ',', '.') . ' per hari selama '
            . $targetHari . ' hari. Semoga target selesai dengan baik!',
            'success'
        );

        return redirect()->route('target-harian.index')->with('sukses', 'Target harian berhasil disimpan!');
    }
}