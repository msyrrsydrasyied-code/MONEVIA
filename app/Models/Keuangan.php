<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Keuangan extends Model
{
    use HasFactory;

    protected $table = 'keuangan';

    /**
     * Baseline 50/30/20. Hanya dipakai sebagai titik awal saat user benar-benar
     * menjalankan aksi penyetionan anggaran, TIDAK pernah disimpan diam-diam
     * sebagai data milik user.
     */
    public const PERSEN_BASELINE = ['kebutuhan' => 50, 'tabungan' => 30, 'hiburan' => 20];

    /**
     * Nama alokasi. Dipakai juga sebagai sumber split nominal yang konsisten.
     */
    public const KUNCI_ALOKASI = ['kebutuhan', 'tabungan', 'hiburan'];

    protected $fillable = [
        'user_id',
        'jumlah_uang',
        'pengeluaran_wajib',
        'target_tabungan',
        'budget_hiburan',
        'uang_disisihkan',
        'target_hari',
        'tanggal_mulai_target',
    ];

    /**
     * Baris keuangan milik user, atau null bila belum pernah diatur.
     *
     * SENGAJA TIDAK memakai firstOrCreate: dulu method `untukUser()` membuat
     * anggaran fiktif (Rp1.500.000 / Rp600.000 / Rp400.000 / Rp300.000) begitu
     * user membuka dashboard, sehingga plan palsu tampil seolah-olah milik user.
     * Sekarang pembacaan murni, dan pembuatan baris hanya terjadi saat user
     * benar-benar menjalankan aksi penyetionan anggaran.
     */
    public static function cariUntukUser(int $userId): ?self
    {
        return static::where('user_id', $userId)->first();
    }

    /**
     * True bila user sudah pernah menyetionkan anggaran sehingga boleh tampil
     * angka pembagian. Tanpa ini, halaman hanya menampilkan ajakan setup.
     */
    public function sudahDiset(): bool
    {
        return $this->exists
            && $this->jumlah_uang !== null
            && (float) $this->jumlah_uang > 0;
    }

    /**
     * Pembagian nominal sesuai makna kolomnya di database.
     */
    public function alokasi(): array
    {
        return [
            'kebutuhan' => (float) $this->pengeluaran_wajib,
            'tabungan' => (float) $this->target_tabungan,
            'hiburan' => (float) $this->budget_hiburan,
        ];
    }

    public function totalAlokasi(): float
    {
        return array_sum($this->alokasi());
    }

    /**
     * Alokasi melebihi uang yang dimiliki?
     *
     * Invariant Monevia: total kebutuhan + tabungan + hiburan tidak boleh lebih
     * besar dari jumlah uang user.
     */
    public function alokasiMelebihiUang(): bool
    {
        return $this->totalAlokasi() > (float) $this->jumlah_uang;
    }

    /**
     * Hitung nominal alokasi dari persentase, dengan sisa pembulatan diberikan
     * ke hiburan supaya totalnya selalu persis sama dengan jumlah uang.
     *
     * @return array{kebutuhan: int, tabungan: int, hiburan: int}
     */
    public static function nominalDariPersen(int $jumlahUang, array $persen): array
    {
        $persenKebutuhan = max(0, min(100, (int) ($persen['kebutuhan'] ?? 0)));
        $persenTabungan = max(0, min(100, (int) ($persen['tabungan'] ?? 0)));

        $kebutuhan = (int) round($jumlahUang * $persenKebutuhan / 100);
        $tabungan = (int) round($jumlahUang * $persenTabungan / 100);

        // Sisa pembulatan jatuh ke hiburan supaya total selalu tepat jumlah uang.
        $hiburan = $jumlahUang - $kebutuhan - $tabungan;

        if ($hiburan < 0) {
            // Persentase tidak valid; jaga agar tidak ada alokasi melebihi uang.
            $hiburan = 0;
            $kebutuhan = min($kebutuhan, $jumlahUang);
            $tabungan = min($tabungan, $jumlahUang - $kebutuhan);
        }

        return [
            'kebutuhan' => $kebutuhan,
            'tabungan' => $tabungan,
            'hiburan' => $hiburan,
        ];
    }

    /**
     * Persentase alokasi yang selalu berjumlah 100, dipakai untuk bar visual.
     * Diambil dari nominal alokasi, bukan dari jumlah_uang, supaya tetap stabil
     * untuk data lama.
     */
    public function persenAlokasi(): array
    {
        $alokasi = $this->alokasi();
        $total = array_sum($alokasi);

        if ($total <= 0) {
            return self::PERSEN_BASELINE;
        }

        $kebutuhan = (int) round($alokasi['kebutuhan'] / $total * 100);
        $tabungan = (int) round($alokasi['tabungan'] / $total * 100);

        return [
            'kebutuhan' => $kebutuhan,
            'tabungan' => $tabungan,
            'hiburan' => max(0, 100 - $kebutuhan - $tabungan),
        ];
    }

    /**
     * Simpan anggaran dengan menjaga invariant alokasi <= jumlah uang.
     * Sumber nilai unik untuk halaman setup.
     */
    public function terapkanAnggaran(int $jumlahUang, array $persen, int $targetHari, ?string $tanggalMulai, float $uangDisisihkan = 0.0): void
    {
        $nominal = self::nominalDariPersen($jumlahUang, $persen);

        $this->jumlah_uang = $jumlahUang;
        $this->pengeluaran_wajib = $nominal['kebutuhan'];
        $this->target_tabungan = $nominal['tabungan'];
        $this->budget_hiburan = $nominal['hiburan'];
        $this->uang_disisihkan = max(0.0, $uangDisisihkan);
        $this->target_hari = $targetHari;
        $this->tanggal_mulai_target = $tanggalMulai;
    }
}