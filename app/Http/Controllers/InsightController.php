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

class InsightController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        // Tanggal awal & akhir bulan ini
        $startThisMonth = Carbon::now()->startOfMonth()->format('Y-m-d');
        $endThisMonth = Carbon::now()->endOfMonth()->format('Y-m-d');

        // Tanggal awal & akhir bulan lalu
        $startLastMonth = Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d');
        $endLastMonth = Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d');

        // 1. DATA KEUANGAN BULAN INI
        $totalPemasukanBulanIni = Transaksi::where('user_id', $userId)
            ->where('jenis', 'pemasukan')
            ->whereBetween('tanggal', [$startThisMonth, $endThisMonth])
            ->sum('jumlah');

        $totalPengeluaranBulanIni = Transaksi::where('user_id', $userId)
            ->where('jenis', 'pengeluaran')
            ->whereBetween('tanggal', [$startThisMonth, $endThisMonth])
            ->sum('jumlah');

        $jumlahTransaksiPengeluaranBulanIni = Transaksi::where('user_id', $userId)
            ->where('jenis', 'pengeluaran')
            ->whereBetween('tanggal', [$startThisMonth, $endThisMonth])
            ->count();

        $rataRataPengeluaran = $jumlahTransaksiPengeluaranBulanIni > 0
            ? round($totalPengeluaranBulanIni / $jumlahTransaksiPengeluaranBulanIni)
            : 0;

        // 2. DATA KEUANGAN BULAN LALU (PEMBANDING)
        $totalPemasukanBulanLalu = Transaksi::where('user_id', $userId)
            ->where('jenis', 'pemasukan')
            ->whereBetween('tanggal', [$startLastMonth, $endLastMonth])
            ->sum('jumlah');

        $totalPengeluaranBulanLalu = Transaksi::where('user_id', $userId)
            ->where('jenis', 'pengeluaran')
            ->whereBetween('tanggal', [$startLastMonth, $endLastMonth])
            ->sum('jumlah');

        // 3. ANALISIS KATEGORI BULAN INI
        $transaksiPengeluaranBulanIni = Transaksi::where('user_id', $userId)
            ->where('jenis', 'pengeluaran')
            ->whereBetween('tanggal', [$startThisMonth, $endThisMonth])
            ->with('kategori')
            ->get();

        $kategoriTotals = [];
        $kategoriCounts = [];

        foreach ($transaksiPengeluaranBulanIni as $t) {
            $namaKat = $t->kategori ? $t->kategori->nama_kategori : 'Lain-lain';
            if (!isset($kategoriTotals[$namaKat])) {
                $kategoriTotals[$namaKat] = 0;
                $kategoriCounts[$namaKat] = 0;
            }
            $kategoriTotals[$namaKat] += $t->jumlah;
            $kategoriCounts[$namaKat] += 1;
        }

        arsort($kategoriTotals);
        arsort($kategoriCounts);

        $kategoriTerbesar = !empty($kategoriTotals) ? key($kategoriTotals) : null;
        $nominalKategoriTerbesar = !empty($kategoriTotals) ? reset($kategoriTotals) : 0;
        $kategoriTerbanyak = !empty($kategoriCounts) ? key($kategoriCounts) : null;
        $frekuensiKategoriTerbanyak = !empty($kategoriCounts) ? reset($kategoriCounts) : 0;

        // 4. KEUANGAN USER & TABUNGAN
        $keuangan = Keuangan::cariUntukUser($userId);

        $targetTabungan = TargetTabungan::where('user_id', $userId)->get();
        $totalTabungan = $targetTabungan->sum('tabungan_saat_ini');

        // Saldo memakai DailyTargetService supaya sama dengan Dashboard dan Laporan
        $saldoReal = DailyTargetService::hitungSaldoTersedia($keuangan, $userId);

        // 5. EVALUASI TARGET HARIAN (STAGE 3 INTEGRATION) - menggunakan DailyTargetService
        $targetInfo = DailyTargetService::calculateDailyTarget($keuangan, $userId);
        $targetHarian = DailyTargetService::getDailyTargetStatusForInsight($keuangan, $userId);

        // 6. ALOKASI ANGGARAN (nilai yang disimpan di Setup Anggaran, bukan dihitung ulang)
        // Belum setup berarti belum ada plan, jadi jangan tampilkan angka.
        $alokasiIdeal = $keuangan ? $keuangan->alokasi() : [
            'kebutuhan' => 0.0,
            'tabungan' => 0.0,
            'hiburan' => 0.0,
        ];

        // 7. GENERATE INSIGHT AUTOMATIC RULES
        $insights = $this->generateInsights(
            $totalPengeluaranBulanIni,
            $totalPengeluaranBulanLalu,
            $totalPemasukanBulanIni,
            $kategoriTerbesar,
            $nominalKategoriTerbesar,
            $keuangan,
            $targetHarian,
            $targetInfo,
            $targetTabungan,
            $userId
        );

        return view('insight.index', compact(
            'totalPemasukanBulanIni',
            'totalPengeluaranBulanIni',
            'jumlahTransaksiPengeluaranBulanIni',
            'rataRataPengeluaran',
            'totalPemasukanBulanLalu',
            'totalPengeluaranBulanLalu',
            'kategoriTerbesar',
            'nominalKategoriTerbesar',
            'kategoriTerbanyak',
            'frekuensiKategoriTerbanyak',
            'keuangan',
            'targetTabungan',
            'totalTabungan',
            'saldoReal',
            'targetHarian',
            'alokasiIdeal',
            'insights'
        ));
    }

    private function generateInsights($pengeluaranBulanIni, $pengeluaranBulanLalu, $pemasukanBulanIni, $kategoriTerbesar, $nominalKategoriTerbesar, $keuangan, $targetHarian, array $targetInfo, $targetTabungan, $userId)
    {
        $insights = [];

        // RULE 6 — Empty State / Tidak ada transaksi
        if ($pengeluaranBulanIni == 0 && $pemasukanBulanIni == 0) {
            $insights[] = [
                'tipe' => 'info',
                'ikon' => '💡',
                'judul' => 'Belum Cukup Data',
                'pesan' => 'Belum ada data pengeluaran atau pemasukan yang dicatat bulan ini. Catat transaksi kamu untuk melihat insight otomatis!'
            ];
            return $insights;
        }

        // RULE 1 & RULE 2 — Perbandingan Bulan Ini vs Bulan Lalu
        if ($pengeluaranBulanLalu > 0) {
            $selisih = $pengeluaranBulanIni - $pengeluaranBulanLalu;
            if ($selisih > 0) {
                $persen = round(($selisih / $pengeluaranBulanLalu) * 100);
                $insights[] = [
                    'tipe' => 'warning',
                    'ikon' => '📈',
                    'judul' => 'Pengeluaran Meningkat',
                    'pesan' => "Pengeluaran kamu bulan ini meningkat Rp " . number_format($selisih, 0, ',', '.') . " ({$persen}%) dibanding bulan sebelumnya."
                ];
            } elseif ($selisih < 0) {
                $hemat = abs($selisih);
                $persen = round(($hemat / $pengeluaranBulanLalu) * 100);
                $insights[] = [
                    'tipe' => 'success',
                    'ikon' => '📉',
                    'judul' => 'Pengeluaran Hemat',
                    'pesan' => "Kamu lebih hemat Rp " . number_format($hemat, 0, ',', '.') . " ({$persen}%) dibanding bulan sebelumnya. Pertahankan!"
                ];
            } else {
                $insights[] = [
                    'tipe' => 'info',
                    'ikon' => '📊',
                    'judul' => 'Pengeluaran Stabil',
                    'pesan' => "Pengeluaran bulan ini sama persis dengan bulan sebelumnya."
                ];
            }
        } else {
            $insights[] = [
                'tipe' => 'info',
                'ikon' => 'ℹ️',
                'judul' => 'Data Pembanding Bulan Lalu',
                'pesan' => "Belum ada data pembanding dari bulan sebelumnya."
            ];
        }

        // RULE 3 — Kategori Terbesar
        if ($kategoriTerbesar) {
            $insights[] = [
                'tipe' => 'info',
                'ikon' => '🏷️',
                'judul' => 'Kategori Pengeluaran Terbesar',
                'pesan' => "Pengeluaran terbesar kamu bulan ini berasal dari kategori \"{$kategoriTerbesar}\" sebesar Rp " . number_format($nominalKategoriTerbesar, 0, ',', '.') . "."
            ];
        }

        // RULE 4 — Rasio pengeluaran terhadap anggaran kebutuhan
        //
        // Basis pembanding harus sama dengan basis pengeluarannya. Sebelumnya
        // pengeluaran bulan ini dibagi sisa uang saat ini, padahal sisa uang itu
        // SUDAH dipotong oleh pengeluaran yang sama, sehingga rasio bisa lompat
        // absurd (mis. 11% lalu 900%). Sekarang dibandingkan dengan anggaran
        // kebutuhan yang seharusnya sudah terpakai sampai hari ini.
        $anggaranKebutuhan = DailyTargetService::anggaranKebutuhanSampaiSekarang($keuangan, $targetInfo);
        $rasio = DailyTargetService::rasioKebutuhan((float) $pengeluaranBulanIni, $anggaranKebutuhan);

        if ($rasio !== null) {
            if ($rasio > 100) {
                $insights[] = [
                    'tipe' => 'danger',
                    'ikon' => '🚨',
                    'judul' => 'Pengeluaran Melebihi Rencana Kebutuhan',
                    'pesan' => "Pengeluaran bulan ini sudah mencapai " . round($rasio) . "% dari rencana kebutuhan sampai hari ini (Rp "
                        . number_format($anggaranKebutuhan, 0, ',', '.') . "). Coba kurangi pengeluaran yang tidak penting."
                ];
            } elseif ($rasio > 85) {
                $insights[] = [
                    'tipe' => 'warning',
                    'ikon' => '⚠️',
                    'judul' => 'Pengeluaran Mendekati Batas',
                    'pesan' => "Pengeluaran bulan ini sudah mencapai " . round($rasio) . "% dari rencana kebutuhan sampai hari ini (Rp "
                        . number_format($anggaranKebutuhan, 0, ',', '.') . "). Pertahankan atau kurangi sedikit."
                ];
            }
        }

        // RULE 5 — Target Tabungan Progress
        if ($targetTabungan->count() > 0) {
            $topTarget = $targetTabungan->sortByDesc('tabungan_saat_ini')->first();
            $progress = $topTarget->jumlah_target > 0 ? min(100, round(($topTarget->tabungan_saat_ini / $topTarget->jumlah_target) * 100)) : 0;

            if ($progress >= 100) {
                $insights[] = [
                    'tipe' => 'success',
                    'ikon' => '🎉',
                    'judul' => 'Target Tabungan Tercapai',
                    'pesan' => "Hebat! Target tabungan \"{$topTarget->nama_target}\" kamu sudah tercapai 100%!"
                ];
            } elseif ($progress >= 70) {
                $insights[] = [
                    'tipe' => 'success',
                    'ikon' => '🎯',
                    'judul' => 'Hampir Tercapai',
                    'pesan' => "Sedikit lagi! Target tabungan \"{$topTarget->nama_target}\" kamu sudah mencapai {$progress}%."
                ];
            } else {
                $insights[] = [
                    'tipe' => 'info',
                    'ikon' => '💰',
                    'judul' => 'Progres Tabungan',
                    'pesan' => "Target tabungan \"{$topTarget->nama_target}\" baru mencapai {$progress}%. Tetap konsisten menabung!"
                ];
            }
        }

        // RULE 6 — Target Harian Status
        if ($targetHarian['is_set']) {
            $daftarWarna = [
                DailyTargetService::STATUS_BOROS => ['danger', '🔴'],
                DailyTargetService::STATUS_PERINGATAN => ['warning', '🟡'],
                DailyTargetService::STATUS_AMAN => ['success', '🟢'],
                DailyTargetService::STATUS_SALDO_HABIS => ['danger', '🛑'],
                DailyTargetService::STATUS_PERIODE_SELESAI => ['info', '🏁'],
                DailyTargetService::STATUS_BELUM_ATUR => ['info', '⚪'],
            ];
            [$tipe, $ikon] = $daftarWarna[$targetHarian['status']] ?? ['info', '⚪'];

            $insights[] = [
                'tipe' => $tipe,
                'ikon' => $ikon,
                'judul' => 'Status Target Harian',
                'pesan' => $targetHarian['pesan']
            ];
        }

        return $insights;
    }
}

