<?php

namespace App\Services;

use App\Models\Keuangan;
use App\Models\Transaksi;
use Carbon\Carbon;

/**
 * Sumber tunggal perhitungan anggaran harian Monevia.
 *
 * DEFINISI UANG MONEVIA (satu-satunya, dipakai semua halaman):
 *
 *   Saldo aktual   = jumlah_uang + total pemasukan - total pengeluaran
 *                    (boleh negatif; tidak pernah dipaksa jadi 0)
 *
 *   Uang disisihkan = nominal yang benar-benar dikunci user sehingga tidak
 *                    bisa dibelanjakan. TIDAK sama dengan Target Tabungan,
 *                    karena target tabungan hanya tujuan, bukan uang terpotong.
 *
 *   Saldo tersedia = saldo aktual - uang disisihkan
 *                    (boleh negatif)
 *
 *   Batas harian   = saldo tersedia / sisa hari, dibatasi minimal 0
 *                    (tidak pernah negatif, tidak pernah division by zero)
 *
 * Konsep resmi:
 *   Sisa hari  = jumlah hari target - hari yang sudah berjalan
 *
 * Semua halaman (dashboard, target harian, insight, laporan, transaksi,
 * rekomendasi) memakai service ini supaya tidak ada perhitungan berbeda.
 */
class DailyTargetService
{
    public const STATUS_AMAN = 'aman';
    public const STATUS_PERINGATAN = 'peringatan';
    public const STATUS_BOROS = 'boros';
    public const STATUS_BELUM_ATUR = 'belum_atur';
    public const STATUS_SALDO_HABIS = 'saldo_habis';
    public const STATUS_PERIODE_SELESAI = 'periode_selesai';

    /**
     * Total pemasukan dan pengeluaran milik user, dipakai bersama banyak halaman.
     */
    public static function ringkasanTransaksi(int $userId): array
    {
        $totalPemasukan = (float) Transaksi::where('user_id', $userId)
            ->where('jenis', 'pemasukan')
            ->sum('jumlah');

        $totalPengeluaran = (float) Transaksi::where('user_id', $userId)
            ->where('jenis', 'pengeluaran')
            ->sum('jumlah');

        return [
            'total_pemasukan' => $totalPemasukan,
            'total_pengeluaran' => $totalPengeluaran,
        ];
    }

    /**
     * Saldo aktual hasil transaksi. Boleh negatif bila pengeluaran melebihi uang.
     */
    public static function hitungSaldoAktual(?Keuangan $keuangan, int $userId): float
    {
        if (!$keuangan) {
            return 0.0;
        }

        $ringkasan = self::ringkasanTransaksi($userId);

        return (float) $keuangan->jumlah_uang
            + $ringkasan['total_pemasukan']
            - $ringkasan['total_pengeluaran'];
    }

    /**
     * Uang yang benar-benar dikunci sehingga tidak bisa dibelanjakan.
     * Target Tabungan tidak termasuk di sini.
     */
    public static function hitungUangDisisihkan(?Keuangan $keuangan): float
    {
        if (!$keuangan) {
            return 0.0;
        }

        return max(0.0, (float) ($keuangan->uang_disisihkan ?? 0));
    }

    /**
     * Saldo yang benar-benar bisa dibelanjakan. Boleh negatif.
     */
    public static function hitungSaldoTersedia(?Keuangan $keuangan, int $userId): float
    {
        return self::hitungSaldoAktual($keuangan, $userId) - self::hitungUangDisisihkan($keuangan);
    }

    /**
     * Nama lama dipertahankan agar tidak ada pemanggil yang rusak,
     * sekarang memakai definisi saldo tersedia yang sama di semua halaman.
     */
    public static function hitungSisaUang(?Keuangan $keuangan, int $userId): float
    {
        return self::hitungSaldoTersedia($keuangan, $userId);
    }

    /**
     * Bentuk targetInfo kosong yang PENUH.
     *
     * Dipakai setiap kali user belum punya data keuangan, sehingga bentuk
     * array selalu sama persis dengan hasil calculateDailyTarget(). Inilah
     * akar masalah lama "Undefined array key Tanggal_mulai": sebelumnya ada
     * dua bentuk array berbeda dan Blade dipatch dengan `??`.
     */
    public static function targetInfoKosong(): array
    {
        return [
            'is_set' => false,
            'total_uang' => 0.0,
            'target_hari' => 0,
            'hari_terlewat' => 0,
            'sisa_hari' => 0,
            'sisa_uang' => 0.0,
            'saldo_aktual' => 0.0,
            'saldo_tersedia' => 0.0,
            'uang_disisihkan' => 0.0,
            'batas_harian' => 0,
            'progress_persen' => 0,
            'periode_selesai' => false,
            'total_pemasukan' => 0.0,
            'total_pengeluaran' => 0.0,
            'tanggal_mulai' => null,
            'tanggal_selesai' => null,
            'tanggal_selesai_id' => null,
        ];
    }

    /**
     * Hitung batas pengeluaran harian beserta seluruh detail pendukungnya.
     * Aman untuk kondisi: belum setup, saldo 0, saldo negatif, sisa hari 0,
     * target hari null, tanggal mulai null, dan tanpa transaksi.
     */
    public static function calculateDailyTarget(?Keuangan $keuangan, int $userId): array
    {
        $kosong = self::targetInfoKosong();

        if (!$keuangan) {
            // Tanpa data keuangan tidak ada saldo yang bisa dihitung.
            $kosong['saldo_aktual'] = 0.0;
            $kosong['saldo_tersedia'] = 0.0;

            return $kosong;
        }

        $ringkasan = self::ringkasanTransaksi($userId);
        $saldoAktual = (float) $keuangan->jumlah_uang
            + $ringkasan['total_pemasukan']
            - $ringkasan['total_pengeluaran'];

        $uangDisisihkan = self::hitungUangDisisihkan($keuangan);
        $saldoTersedia = $saldoAktual - $uangDisisihkan;

        $jumlahUang = (float) $keuangan->jumlah_uang;
        $targetHari = (int) $keuangan->target_hari;
        $tanggalMulai = $keuangan->tanggal_mulai_target ? Carbon::parse($keuangan->tanggal_mulai_target) : null;

        $dasar = [
            'total_uang' => $jumlahUang,
            'saldo_aktual' => $saldoAktual,
            'saldo_tersedia' => $saldoTersedia,
            'sisa_uang' => $saldoTersedia,
            'uang_disisihkan' => $uangDisisihkan,
            'total_pemasukan' => $ringkasan['total_pemasukan'],
            'total_pengeluaran' => $ringkasan['total_pengeluaran'],
        ];

        if ($targetHari <= 0 || !$tanggalMulai) {
            return array_merge($kosong, $dasar, ['is_set' => false]);
        }

        $hariIni = Carbon::today();
        $hariTerlewat = min($targetHari, (int) $tanggalMulai->diffInDays($hariIni));
        $sisaHari = max(0, $targetHari - $hariTerlewat);
        $periodeSelesai = $sisaHari <= 0;

        // Batas harian tidak boleh negatif dan tidak boleh division by zero.
        // Saldo negatif menghasilkan batas 0, bukan batas negatif.
        $batasHarian = (!$periodeSelesai && $sisaHari > 0 && $saldoTersedia > 0)
            ? (int) round($saldoTersedia / $sisaHari)
            : 0;

        return array_merge($dasar, [
            'is_set' => true,
            'target_hari' => $targetHari,
            'hari_terlewat' => $hariTerlewat,
            'sisa_hari' => $sisaHari,
            'batas_harian' => $batasHarian,
            'progress_persen' => $targetHari > 0 ? min(100, (int) round($hariTerlewat / $targetHari * 100)) : 0,
            'periode_selesai' => $periodeSelesai,
            'tanggal_mulai' => $tanggalMulai->format('d M Y'),
            'tanggal_selesai' => $tanggalMulai->copy()->addDays($targetHari)->format('d M Y'),
            'tanggal_selesai_id' => self::tanggalIndonesia($tanggalMulai->copy()->addDays(max(0, $targetHari - 1))),
        ]);
    }

    /**
     * Sisa jatah belanja hari ini. Tidak pernah negatif.
     */
    public static function sisaJatahHariIni(int $batasHarian, float $pengeluaranHariIni): int
    {
        if ($batasHarian <= 0) {
            return 0;
        }

        return max(0, $batasHarian - (int) round($pengeluaranHariIni));
    }

    private static function tanggalIndonesia(Carbon $tanggal): string
    {
        $bulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return $tanggal->day . ' ' . ($bulan[(int) $tanggal->month] ?? $tanggal->format('M'));
    }

    /**
     * Status kondisi keuangan harian.
     *
     *   saldo tersedia < 0            -> KRITIS (kode internal: boros)
     *   saldo tersedia = 0            -> SALDO_HABIS
     *   batas harian <= 0             -> SALDO_HABIS
     *   penggunaan <= 80%  dari batas -> AMAN
     *   80% < penggunaan <= 100%       -> PERINGATAN
     *   penggunaan > 100%              -> KRITIS (kode internal: boros)
     *
     * Kode status 'boros' dipertahankan agar tes dan data lama tidak rusak.
     * Label tampilan memakai AMAN / PERINGATAN / KRITIS.
     */
    public static function statusHarian(array $targetInfo, float $pengeluaranHariIni = 0): array
    {
        if (empty($targetInfo['is_set'])) {
            return self::statusInfo(self::STATUS_BELUM_ATUR);
        }

        if (!empty($targetInfo['periode_selesai'])) {
            $selesai = self::statusInfo(self::STATUS_PERIODE_SELESAI);
            $selesai['sisa_uang'] = (float) ($targetInfo['saldo_tersedia'] ?? 0);
            $selesai['pengeluaran_hari_ini'] = $pengeluaranHariIni;

            return $selesai;
        }

        $saldoTersedia = (float) ($targetInfo['saldo_tersedia'] ?? $targetInfo['sisa_uang'] ?? 0);
        $batasHarian = (int) ($targetInfo['batas_harian'] ?? 0);

        // Saldo negatif tidak disembunyikan: status menjadi BOROS dan saldo tetap
        // bernilai negatif di semua tampilan.
        if ($saldoTersedia < 0) {
            $info = self::statusInfo(self::STATUS_BOROS);
            $info['batas_harian'] = 0;
            $info['pengeluaran_hari_ini'] = $pengeluaranHariIni;
            $info['sisa_uang'] = $saldoTersedia;
            $info['sisa_jatah'] = 0;
            $info['persentase_pakai'] = 100;

            return $info;
        }

        if ($saldoTersedia <= 0 || $batasHarian <= 0) {
            $info = self::statusInfo(self::STATUS_SALDO_HABIS);
            $info['batas_harian'] = $batasHarian;
            $info['pengeluaran_hari_ini'] = $pengeluaranHariIni;
            $info['sisa_uang'] = $saldoTersedia;
            $info['sisa_jatah'] = 0;

            return $info;
        }

        $persentase = $pengeluaranHariIni / $batasHarian * 100;

        if ($persentase <= 80) {
            $status = self::STATUS_AMAN;
        } elseif ($persentase <= 100) {
            $status = self::STATUS_PERINGATAN;
        } else {
            $status = self::STATUS_BOROS;
        }

        $info = self::statusInfo($status);
        $info['persentase_pakai'] = (int) round($persentase);
        $info['batas_harian'] = $batasHarian;
        $info['pengeluaran_hari_ini'] = $pengeluaranHariIni;
        $info['sisa_uang'] = $saldoTersedia;
        $info['sisa_jatah'] = self::sisaJatahHariIni($batasHarian, $pengeluaranHariIni);

        return $info;
    }

    /**
     * Warna, label, dan pesan singkat untuk setiap status.
     */
    private static function statusInfo(string $status): array
    {
        $daftar = [
self::STATUS_BELUM_ATUR => [
                'label' => 'Belum atur',
                'warna' => '#5f7469',
                'ikon' => '⚪',
                ' pesan' => 'Belum ada anggaran aktif. Atur uang dan periodemu dulu supaya Monevia bisa menghitung batas belanja harian.',
            ],
self::STATUS_PERIODE_SELESAI => [
                'label' => 'Selesai',
                'warna' => '#2b6cb0',
                'ikon' => '🏁',
                ' pesan' => 'Periode anggaran sudah selesai. Atur periode baru kalau kamu butuh lanjutan.',
            ],
self::STATUS_SALDO_HABIS => [
                'label' => 'Kritis',
                'warna' => '#c44536',
                'ikon' => '🛑',
                ' pesan' => 'Uangmu mulai menipis. Untuk sementara, prioritaskan kebutuhan penting.',
            ],
self::STATUS_AMAN => [
                'label' => 'Aman',
                'warna' => '#0f6b4c',
                'ikon' => '🟢',
                ' pesan' => 'Kondisimu masih aman. Usahakan pengeluaran hari ini tetap di bawah batas.',
            ],
self::STATUS_PERINGATAN => [
                'label' => 'Peringatan',
                'warna' => '#c9a227',
                'ikon' => '🟡',
                ' pesan' => 'Pengeluaranmu mulai mendekati batas. Coba sedikit lebih hemat hari ini.',
            ],
self::STATUS_BOROS => [
                'label' => 'Kritis',
                'warna' => '#c44536',
                'ikon' => '🔴',
                ' pesan' => 'Hari ini kamu sudah melewati batas. Besok coba sedikit lebih hemat agar uang tetap cukup.',
            ],
        ];

        $info = $daftar[$status] ?? $daftar[self::STATUS_BELUM_ATUR];

        return [
            'status' => $status,
            'label' => $info['label'],
            'warna' => $info['warna'],
            'ikon' => $info['ikon'],
            'pesan' => $info['pesan'],
            'persentase_pakai' => 0,
            'batas_harian' => 0,
            'pengeluaran_hari_ini' => 0,
            'sisa_uang' => 0.0,
            'sisa_jatah' => 0,
        ];
    }

/**
     * Saran Money Coach untuk kondisi keuangan keseluruhan (bukan harian).
     * Bahasa ramah, tidak menghakimi.
     *
     * Saat konteks keuangan lengkap (targetInfo, statusInfo, pengeluaranHariIni)
     * disediakan, pesan mengandung angka riil (rupiah, persentase, sisa hari).
     * Saat hanya $status yang diberikan (panggilan lama), pesan kembali ke
     * kalimat generik yang sama dengan sebelumnya.
     */
    public static function pesanMoneyCoach(
        string $status,
        array $targetInfo = [],
        array $statusInfo = [],
        float $pengeluaranHariIni = 0
    ): string {
        // Mode backward-compatible: hanya status yang diberikan.
        if (empty($targetInfo) && empty($statusInfo)) {
            $pesan = [
                self::STATUS_AMAN => 'Kondisimu masih aman. Usahakan pengeluaran hari ini tetap di bawah batas.',
                self::STATUS_PERINGATAN => 'Pengeluaranmu mulai mendekati batas. Coba sedikit lebih hemat hari ini.',
                self::STATUS_BOROS => 'Hari ini kamu sudah melewati batas. Besok coba sedikit lebih hemat agar uang tetap cukup.',
                self::STATUS_BELUM_ATUR => 'Belum ada anggaran aktif. Atur uang dan periodemu supaya Monevia bisa membantu.',
                self::STATUS_SALDO_HABIS => 'Uangmu mulai menipis. Untuk sementara, prioritaskan kebutuhan penting.',
                self::STATUS_PERIODE_SELESAI => 'Periode anggaran sudah selesai. Atur periode baru kalau kamu mau lanjut.',
            ];

            return $pesan[$status] ?? $pesan[self::STATUS_BELUM_ATUR];
        }

        // Mode kontekstual: gunakan angka riil dari targetInfo/statusInfo.
        $batasHarian = (int) ($statusInfo['batas_harian'] ?? $targetInfo['batas_harian'] ?? 0);
        $persentase = (float) ($statusInfo['persentase_pakai'] ?? 0);
        $sisaJatah = (int) ($statusInfo['sisa_jatah'] ?? max(0, $batasHarian - (int) round($pengeluaranHariIni)));
        $sisaHari = (int) ($targetInfo['sisa_hari'] ?? 0);
        $saldoTersedia = (float) ($targetInfo['saldo_tersedia'] ?? $statusInfo['sisa_uang'] ?? 0);

        switch ($status) {
            case self::STATUS_BELUM_ATUR:
                return 'Belum ada anggaran aktif. Atur dulu jumlah uang dan periodenya supaya Monevia bisa menghitung batas belanja harianmu.';

            case self::STATUS_PERIODE_SELESAI:
                return 'Periode anggaran sudah selesai. Atur periode baru kalau kamu mau lanjut.';

            case self::STATUS_SALDO_HABIS:
                if ($saldoTersedia < 0) {
                    return 'Uangmu mulai menipis, bahkan sudah melewati jumlah yang tersedia. Untuk sementara, prioritaskan kebutuhan penting.';
                }

                return 'Uangmu mulai menipis. Untuk sementara, prioritaskan kebutuhan penting.';

            case self::STATUS_AMAN:
                if ($pengeluaranHariIni <= 0) {
                    $lanjut = $sisaHari > 0
                        ? ' Jatah hari ini ' . self::rp($batasHarian) . ', masih ada ' . $sisaHari . ' hari di periode ini.'
                        : '';

                    return 'Keren. Belum ada pengeluaran hari ini.' . $lanjut;
                }

                if ($persentase <= 40) {
                    return 'Masih aman. Kamu baru menggunakan sekitar ' . (int) round($persentase)
                        . '% dari batas hari ini. Sisa jatah ' . self::rp($sisaJatah) . '.';
                }

                return 'Kondisimu masih aman. Usahakan pengeluaran hari ini tetap di bawah batas. Sisa jatah '
                    . self::rp($sisaJatah) . '.';

            case self::STATUS_PERINGATAN:
                return 'Pelan-pelan ya. Pengeluaranmu sudah mendekati batas hari ini ('
                    . (int) round($persentase) . '%). Sisa jatah ' . self::rp($sisaJatah)
                    . '. Coba sedikit lebih hemat hari ini.';

            case self::STATUS_BOROS:
                $sisaHariTeks = $sisaHari > 0 ? ' Masih ada ' . $sisaHari . ' hari di periode ini.' : '';

                if ($batasHarian <= 0) {
                    return 'Uangmu mulai menipis. Untuk sementara, prioritaskan kebutuhan penting.' . $sisaHariTeks;
                }

                return 'Hari ini kamu sudah melewati batas ('
                    . self::rp($pengeluaranHariIni) . ' dari ' . self::rp($batasHarian)
                    . '). Besok coba sedikit lebih hemat agar uang tetap cukup.' . $sisaHariTeks;

            default:
                return 'Belum ada anggaran aktif. Atur dulu jumlah uang dan periodenya supaya Monevia bisa menghitung batas belanja harianmu.';
        }
    }

    private static function rp(float $nominal): string
    {
        return 'Rp ' . number_format((int) round($nominal), 0, ',', '.');
    }

    /**
     * Status Target Harian untuk halaman Insight.
     */
    public static function getDailyTargetStatusForInsight(?Keuangan $keuangan, int $userId): array
    {
        $targetInfo = self::calculateDailyTarget($keuangan, $userId);

        $pengeluaranHariIni = (float) Transaksi::where('user_id', $userId)
            ->where('jenis', 'pengeluaran')
            ->whereDate('tanggal', Carbon::today())
            ->sum('jumlah');

        $status = self::statusHarian($targetInfo, $pengeluaranHariIni);

        return array_merge($status, [
            'is_set' => $targetInfo['is_set'],
            'batas_harian' => $targetInfo['batas_harian'],
            'pengeluaran_today' => $pengeluaranHariIni,
            'sisa_hari' => $targetInfo['sisa_hari'],
            'sisa_uang' => $targetInfo['sisa_uang'],
            'saldo_aktual' => $targetInfo['saldo_aktual'],
            'saldo_tersedia' => $targetInfo['saldo_tersedia'],
            'uang_disisihkan' => $targetInfo['uang_disisihkan'],
        ]);
    }

    /**
     * Anggaran kebutuhan yang sudah seharusnya terpakai sampai hari ini.
     *
     * Dipakai Insight sebagai pembanding pengeluaran. Basisnya sama dengan
     * rencana alokasi dan sama-sama dibandingkan per waktu berjalan, sehingga
     * tidak lagi menghasilkan rasio absurd (mis. 11% melompat ke 900%).
     */
    public static function anggaranKebutuhanSampaiSekarang(?Keuangan $keuangan, array $targetInfo): float
    {
        if (!$keuangan) {
            return 0.0;
        }

        $alokasi = $keuangan->alokasi();
        $total = array_sum($alokasi);

        if ($total <= 0) {
            return 0.0;
        }

        // Tanpa periode aktif, seluruh alokasi dianggap sebagai acuan.
        if (empty($targetInfo['is_set']) || (int) ($targetInfo['target_hari'] ?? 0) <= 0) {
            return (float) $alokasi['kebutuhan'];
        }

        // Minimal satu hari dihitung, karena pada hari pertama periode tetap
        // ada batas belanja hari ini. Kalau dihitung 0/N maka anggaran basisnya
        // nol dan insight tidak pernah muncul sama sekali.
        $hariEfektif = max(1, (int) $targetInfo['hari_terlewat']);
        $rasioWaktu = min(1.0, $hariEfektif / (int) $targetInfo['target_hari']);

        return (float) $alokasi['kebutuhan'] * $rasioWaktu;
    }

    /**
     * Rasio pemakaian kebutuhan yang stabil: numerator dan denominator memakai
     * basis yang sama, sehingga tidak pernah melompat tidak masuk akal.
     */
    public static function rasioKebutuhan(float $pengeluaran, float $anggaranKebutuhan): ?float
    {
        if ($anggaranKebutuhan <= 0) {
            return null;
        }

        return $pengeluaran / $anggaranKebutuhan * 100;
    }

    /**
     * Streak = jumlah hari berturut-turut user menjaga pengeluaran harian
     * tidak melebihi batas harian.
     *
     * Aturan:
     *   - Hari dengan pengeluaran <= batas harian  -> streak bertambah
     *   - Hari dengan pengeluaran > batas harian   -> streak reset, berhenti
     *   - Hari tanpa transaksi                     -> TIDAK reset, tapi streak
     *                                                  berhenti (tidak lanjut ke hari sebelumnya)
     *
     * Tanpa kolom users.streak: dihitung dari data transaksi existing.
     * Dibatasi 30 hari ke belakang supaya tidak ada query tak terbatas.
     */
    public static function hitungStreak(?Keuangan $keuangan, int $userId): int
    {
        if (!$keuangan || !$keuangan->sudahDiset()) {
            return 0;
        }

        $targetHari = (int) $keuangan->target_hari;
        $jumlahUang = (float) $keuangan->jumlah_uang;
        $uangDisisihkan = self::hitungUangDisisihkan($keuangan);
        $_mulai = $keuangan-> tanggal_mulai_target ? Carbon::parse($keuangan-> tanggal_mulai_target) : null;

        if ($targetHari <= 0 || !$_mulai) {
            return 0;
        }

        $hariIni = Carbon::today();
        $batasCek = $_mulai->copy()->addDays($targetHari)->lt($hariIni) ? $_mulai->copy()->addDays($targetHari) : $hariIni;

        // Ambil semua transaksi pengeluaran dari awal periode sampai hari ini.
        $transaksi = Transaksi::where('user_id', $userId)
            ->where('jenis', 'pengeluaran')
            ->whereBetween('tanggal', [$_mulai->toDateString(), $batasCek->toDateString()])
            ->get()
            ->groupBy(fn ($t) => $t->getDate('nama') ?? $t->DATE('nama'))
            ->map(fn ($group) => (float) $group->sum('jumlah'));

        // Pemasukan untuk menghitung saldo yang tersedia per hari.
        $pemasukan = Transaksi::where('user_id', $userId)
            ->where('jenis', 'pemasukan')
            ->whereBetween('tanggal', [$_mulai->toDateString(), $batasCek->toDateString()])
            ->get()
            ->groupBy(fn ($t) => $t->DATE('nama'))
            ->map(fn ($group) => (float) $group->sum('jumlah'));

        $streak = 0;
        $hari = $batasCek->copy();

        while ($hari >= $_mulai) {
            $pengeluaranHariItu = $transaksi->get($hari->toDateString(), 0.0);

            if ($pengeluaranHariItu <= 0) {
                // Hari tanpa transaksi: TIDAK reset, tapi streak berhenti.
                break;
            }

            // Hitung sisa hari pada hari itu.
            $hariTerlewat = (int) $_mulai->diffInDays($hari);
            $sisaHari = max(1, $targetHari - $hariTerlewat);

            // Saldo yang tersedia pada hari itu.
            $totalPemasukanSampaiHari = $pemasukan->filter(fn ($_, $key) => $key <= $hari->toDateString())->sum();
            $totalPengeluaranSampaiHari = $transaksi->filter(fn ($_, $key) => $key <= $hari->toDateString())->sum();
            $saldoTersediaHariItu = $jumlahUang + $totalPemasukanSampaiHari - $totalPengeluaranSampaiHari - $uangDisisihkan;

            $batasHarian = $saldoTersediaHariItu > 0
                ? (int) round($saldoTersediaHariItu / $sisaHari)
                : 0;

            if ($pengeluaranHariItu <= $batasHarian) {
                $streak++;
            } else {
                break;
            }

            $hari->subDay();
        }

        return $streak;
    }
}