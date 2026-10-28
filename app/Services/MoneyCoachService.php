<?php

namespace App\Services;

/**
 * Money Coach — satu pesan kontekstual berdasarkan kondisi keuangan aktual.
 *
 * Bukan chat, bukan AI. Memakai angka dari DailyTargetService.
 */
class MoneyCoachService
{
    /**
     * @param array $targetInfo  hasil DailyTargetService::calculateDailyTarget
     * @param array $statusInfo  hasil DailyTargetService::statusHarian
     * @param float $pengeluaranHariIni
     * @param array{nama?: string, jenis?: string, jumlah?: float}|null $transaksiTerbaru
     */
    public static function pesan(
        array $targetInfo,
        array $statusInfo,
        float $pengeluaranHariIni = 0,
        ?array $transaksiTerbaru = null
    ): string {
        $status = $statusInfo['status'] ?? DailyTargetService::STATUS_BELUM_ATUR;
        $batasHarian = (int) ($statusInfo['batas_harian'] ?? $targetInfo['batas_harian'] ?? 0);
        $persentase = (float) ($statusInfo['persentase_pakai'] ?? 0);
        $sisaJatah = (int) ($statusInfo['sisa_jatah'] ?? DailyTargetService::sisaJatahHariIni($batasHarian, $pengeluaranHariIni));
        $sisaHari = (int) ($targetInfo['sisa_hari'] ?? 0);
        $saldoTersedia = (float) ($targetInfo['saldo_tersedia'] ?? $statusInfo['sisa_uang'] ?? 0);

        if ($transaksiTerbaru && ($transaksiTerbaru['jenis'] ?? '') === 'pemasukan') {
            return self::pesanPemasukan((float) ($transaksiTerbaru['jumlah'] ?? 0), $sisaJatah, $batasHarian);
        }

        switch ($status) {
            case DailyTargetService::STATUS_BOROS:
                return self::kritisMessage($pengeluaranHariIni, $batasHarian, $sisaHari);

            case DailyTargetService::STATUS_PERINGATAN:
                return self::waspadaMessage($persentase, $sisaJatah);

            case DailyTargetService::STATUS_AMAN:
                return self::amanMessage($persentase, $sisaJatah, $batasHarian, $sisaHari);

            case DailyTargetService::STATUS_SALDO_HABIS:
                return self::saldoHabisMessage($saldoTersedia);

            case DailyTargetService::STATUS_PERIODE_SELESAI:
                return 'Periode anggaran sudah selesai. Atur periode baru kalau kamu mau lanjut.';

            case DailyTargetService::STATUS_BELUM_ATUR:
            default:
                return 'Belum ada anggaran aktif. Atur dulu jumlah uang dan periodenya supaya Monevia bisa menghitung batas belanja harianmu.';
        }
    }

    public static function pesanMelewatiBatas(float $pengeluaranHariIni, int $batasHarian): string
    {
        return 'Hari ini kamu sudah melewati batas ('
            . self::rp($pengeluaranHariIni) . ' dari ' . self::rp($batasHarian)
            . '). Besok coba sedikit lebih hemat agar uang tetap cukup.';
    }

    public static function pesanMasihAman(float $sisaBatas, int $batasHarian): string
    {
        return 'Masih aman. Kamu masih punya ' . self::rp($sisaBatas)
            . ' dari batas harian ' . self::rp($batasHarian) . '.';
    }

    private static function amanMessage(float $persentase, int $sisaJatah, int $batasHarian, int $sisaHari): string
    {
        if ($pengeluaran = $persentase <= 0) {
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
    }

    private static function waspadaMessage(float $persentase, int $sisaJatah): string
    {
        return 'Pelan-pelan ya. Pengeluaranmu sudah mendekati batas hari ini ('
            . (int) round($persentase) . '%). Sisa jatah ' . self::rp($sisaJatah)
            . '. Coba sedikit lebih hemat hari ini.';
    }

    private static function kritisMessage(float $pengeluaranHariIni, int $batasHarian, int $sisaHari): string
    {
        $sisaHariTeks = $sisaHari > 0 ? ' Masih ada ' . $sisaHari . ' hari di periode ini.' : '';

        if ($batasHarian <= 0) {
            return 'Uangmu mulai menipis. Untuk sementara, prioritaskan kebutuhan penting.' . $sisaHariTeks;
        }

        return 'Hari ini kamu sudah melewati batas ('
            . self::rp($pengeluaranHariIni) . ' dari ' . self::rp($batasHarian)
            . '). Besok coba sedikit lebih hemat agar uang tetap cukup.' . $sisaHariTeks;
    }

    private static function saldoHabisMessage(float $saldoTersedia): string
    {
        if ($saldoTersedia < 0) {
            return 'Uangmu mulai menipis, bahkan sudah melewati jumlah yang tersedia. Untuk sementara, prioritaskan kebutuhan penting.';
        }

        return 'Uangmu mulai menipis. Untuk sementara, prioritaskan kebutuhan penting.';
    }

    private static function pesanPemasukan(float $jumlah, int $sisaJatah, int $batasHarian): string
    {
        if ($batasHarian <= 0) {
            return 'Pemasukan ' . self::rp($jumlah) . ' sudah dicatat. Cek lagi batas belanja hari ini di dashboard.';
        }

        return 'Pemasukan ' . self::rp($jumlah) . ' sudah dicatat. Batas belanja hari ini sekarang '
            . self::rp($batasHarian) . ', sisa jatah ' . self::rp($sisaJatah) . '.';
    }

    private static function rp(float $nominal): string
    {
        return 'Rp ' . number_format((int) round($nominal), 0, ',', '.');
    }
}
