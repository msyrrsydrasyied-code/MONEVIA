<?php

namespace App\Http\Controllers;

use App\Models\Keuangan;
use App\Models\Notification;
use App\Services\DailyTargetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class SetupAnggaranController extends Controller
{
    /**
     * Pilihan periode anggaran yang didukung Monevia.
     */
    public const PERIODE = [7, 14, 30, 60, 90];

    public const LABEL_PERIODE = [
        7 => '1 Minggu',
        14 => '2 Minggu',
        30 => '1 Bulan',
        60 => '2 Bulan',
        90 => '3 Bulan',
    ];

    /**
     * Batas nominal aman sesuai kolom decimal(15,2) pada tabel keuangan.
     */
    public const NOMINAL_MAKS = 999999999999;

    /**
     * Halaman Setup Anggaran Terpadu.
     * Tidak pernah membuat data baru otomatis: hanya membaca anggaran aktif milik user login.
     */
    public function index()
    {
        $userId = Auth::id();
        $keuangan = Keuangan::where('user_id', $userId)->first();

        // Angka absolut hanya boleh dibaca dari data yang benar-benar ada.
        // Tidak ada lagi default fiktif yang terlihat seperti milik user.
        $sudahSetup = $keuangan && $keuangan->sudahDiset();

        if ($sudahSetup) {
            $jumlahUang = (int) round((float) $keuangan->jumlah_uang);
            $kebutuhan = (int) round((float) $keuangan->pengeluaran_wajib);
            $tabungan = (int) round((float) $keuangan->target_tabungan);
            $hiburan = (int) round((float) $keuangan->budget_hiburan);
            $uangDisisihkan = (int) round((float) ($keuangan->uang_disisihkan ?? 0));
        } else {
            // Baseline 50/30/20 hanya untuk mengisi slider form sebagai contoh.
            // Nominalnya nol supaya tidak pernah tampak seperti data milik user.
            $jumlahUang = 0;
            $kebutuhan = 0;
            $tabungan = 0;
            $hiburan = 0;
            $uangDisisihkan = 0;
        }

        $periode = $keuangan && $keuangan->target_hari
            ? (int) $keuangan->target_hari
            : 30;
        if (!in_array($periode, self::PERIODE, true)) {
            $periode = 30;
        }

        $persen = self::hitungPersen($kebutuhan, $tabungan, $hiburan);

        // Bentuk targetInfo SELALU sama persis, berasal dari service.
        // Inilah akar masalah lama "Undefined array key Tanggal_mulai":
        // sebelumnya controller membangun array 5-key sendiri, sedangkan hasil
        // service punya 14 key. Sekarang tidak ada dua bentuk lagi.
        $targetInfo = DailyTargetService::calculateDailyTarget($keuangan, $userId);

        // Tanggal periode yang sedang berjalan, sudah diformat satu kali di
        // controller supaya Blade tidak perlu menambal dengan `??`.
        $tanggalMulaiBerjalan = $keuangan && $keuangan->tanggal_mulai_target
            ? \Carbon\Carbon::parse($keuangan->tanggal_mulai_target)->format('d M Y')
            : null;

        $periodeOpsi = [];
        foreach (self::PERIODE as $hari) {
            $periodeOpsi[$hari] = $hari . ' Hari (' . (self::LABEL_PERIODE[$hari] ?? 'Kustom') . ')';
        }

        return view('setup_anggaran.index', compact(
            'keuangan',
            'sudahSetup',
            'uangDisisihkan',
            'jumlahUang',
            'kebutuhan',
            'tabungan',
            'hiburan',
            'periode',
            'persen',
            'targetInfo',
            'tanggalMulaiBerjalan',
            'periodeOpsi'
        ));
    }

    /**
     * Menyimpan setup anggaran terpadu (nominal + periode + alokasi) dalam satu baris keuangan user.
     */
    public function simpan(Request $request)
    {
        $this->normalisasiNominal($request);

        $request->validate([
            'jumlah_uang' => ['required', 'integer', 'min:1', 'max:' . self::NOMINAL_MAKS],
            'periode_hari' => ['required', 'integer', Rule::in(self::PERIODE)],
            'persen_kebutuhan' => ['required', 'integer', 'min:0', 'max:100'],
            'persen_tabungan' => ['required', 'integer', 'min:0', 'max:100'],
            'persen_hiburan' => ['required', 'integer', 'min:0', 'max:100'],
            'mulai_ulang' => ['nullable', 'boolean'],
            'uang_disisihkan' => ['nullable', 'integer', 'min:0'],
        ], [
            'jumlah_uang.required' => 'Jumlah uang wajib diisi.',
            'jumlah_uang.integer' => 'Jumlah uang harus berupa angka.',
            'jumlah_uang.min' => 'Jumlah uang minimal Rp1.',
            'jumlah_uang.max' => 'Jumlah uang terlalu besar untuk disimpan.',
            'periode_hari.required' => 'Pilih periode anggaran.',
            'periode_hari.in' => 'Periode anggaran yang dipilih tidak tersedia.',
            'persen_kebutuhan.required' => 'Tentukan persentase kebutuhan.',
            'persen_tabungan.required' => 'Tentukan persentase tabungan.',
            'persen_hiburan.required' => 'Tentukan persentase hiburan.',
            'persen_kebutuhan.integer' => 'Persentase kebutuhan harus berupa angka.',
            'persen_tabungan.integer' => 'Persentase tabungan harus berupa angka.',
            'persen_hiburan.integer' => 'Persentase hiburan harus berupa angka.',
            'persen_kebutuhan.min' => 'Persentase kebutuhan minimal 0%.',
            'persen_kebutuhan.max' => 'Persentase kebutuhan maksimal 100%.',
            'persen_tabungan.min' => 'Persentase tabungan minimal 0%.',
            'persen_tabungan.max' => 'Persentase tabungan maksimal 100%.',
            'persen_hiburan.min' => 'Persentase hiburan minimal 0%.',
            'persen_hiburan.max' => 'Persentase hiburan maksimal 100%.',
            'uang_disisihkan.min' => 'Uang yang disisihkan tidak boleh bernilai negatif.',
            'uang_disisihkan.integer' => 'Uang yang disisihkan harus berupa angka bulat.',
            'uang_disisihkan.max' => 'Uang yang disisihkan tidak boleh melebihi jumlah uang.',
        ]);

        $totalPersen = (int) $request->persen_kebutuhan
            + (int) $request->persen_tabungan
            + (int) $request->persen_hiburan;

        if ($totalPersen !== 100) {
            return back()
                ->withInput()
                ->withErrors([
                    'persen_kebutuhan' => 'Total persentase harus tepat 100%. Saat ini ' . $totalPersen . '%.',
                ]);
        }

        $userId = Auth::id();
        $jumlahUang = (int) $request->jumlah_uang;
        $periode = (int) $request->periode_hari;
        $uangDisisihkan = (int) ($request->uang_disisihkan ?? 0);

        // Pembagian nominal dihitung satu kali lewat model, bukan rumus terpisah,
        // sehingga Dashboard/Rekomendasi/Insight memakai angka yang sama persis.
        $nominal = Keuangan::nominalDariPersen($jumlahUang, [
            'kebutuhan' => (int) $request->persen_kebutuhan,
            'tabungan' => (int) $request->persen_tabungan,
            'hiburan' => (int) $request->persen_hiburan,
        ]);

        $kebutuhan = $nominal['kebutuhan'];
        $tabungan = $nominal['tabungan'];
        $hiburan = $nominal['hiburan'];

        // Jaring pengaman: total alokasi tidak boleh melebihi jumlah uang.
        if (($kebutuhan + $tabungan + $hiburan) > $jumlahUang) {
            return back()->withInput()->withErrors([
                'jumlah_uang' => 'Total pembagian tidak boleh melebihi jumlah uang yang tersedia.',
            ]);
        }

        if ($uangDisisihkan > $jumlahUang) {
            return back()->withInput()->withErrors([
                'uang_disisihkan' => 'Uang yang disisihkan (Rp ' . number_format($uangDisisihkan, 0, ',', '.')
                    . ') tidak boleh melebihi jumlah uang yang tersedia (Rp ' . number_format($jumlahUang, 0, ',', '.') . ').',
            ]);
        }

        DB::transaction(function () use ($request, $userId, $jumlahUang, $periode, $kebutuhan, $tabungan, $hiburan, $uangDisisihkan) {
            $sebelumnya = Keuangan::cariUntukUser($userId);

            // Aturan reset: tanggal mulai baru hanya bila pengguna meminta atau belum pernah ada.
            // Bila tidak, tanggal mulai lama dipertahankan agar sisa hari dan transaksi lama
            // tetap konsisten serta saldo tidak dihitung dua kali.
            $mulaiBaru = (bool) $request->mulai_ulang
                || !$sebelumnya
                || !$sebelumnya->tanggal_mulai_target;

            $tanggalMulai = $mulaiBaru
                ? Carbon::today()
                : Carbon::parse($sebelumnya->tanggal_mulai_target);

            // Satu baris per user (dijamin UNIQUE index di level database).
            $keuangan = $sebelumnya ?: new Keuangan(['user_id' => $userId]);
            $keuangan->jumlah_uang = $jumlahUang;
            $keuangan->pengeluaran_wajib = $kebutuhan;
            $keuangan->target_tabungan = $tabungan;
            $keuangan->budget_hiburan = $hiburan;
            $keuangan->uang_disisihkan = $uangDisisihkan;
            $keuangan->target_hari = $periode;
            $keuangan->tanggal_mulai_target = $tanggalMulai->toDateString();
            $keuangan->save();

            $batasHarian = (int) round($jumlahUang / max(1, $periode));

            Notification::createNotification(
                $userId,
                $sebelumnya ? 'Anggaran Diperbarui' : 'Anggaran Siap',
                sprintf(
                    'Kebutuhan Rp%s • Tabungan Rp%s • Hiburan Rp%s. Perkiraan batas harian Rp%s selama %d hari.',
                    number_format($kebutuhan, 0, ',', '.'),
                    number_format($tabungan, 0, ',', '.'),
                    number_format($hiburan, 0, ',', '.'),
                    number_format($batasHarian, 0, ',', '.'),
                    $periode
                ),
                'success'
            );
        });

        return redirect()
            ->route('dashboard')
            ->with('sukses', 'Anggaran siap! Kamu bisa pantau progresnya di dashboard.');
    }

    /**
     * Membersihkan input nominal sebelum validasi.
     * Menerima "2000000", "2.000.000", "200k", "1,5 juta", dan menolak nilai negatif.
     */
    private function normalisasiNominal(Request $request): void
    {
        $mentah = $request->input('jumlah_uang');

        if (!is_string($mentah)) {
            return;
        }

        $request->merge(['jumlah_uang' => self::uraiNominal($mentah)]);
    }

    public static function uraiNominal(string $mentah): ?int
    {
        $mentah = trim($mentah);

        if ($mentah === '') {
            return null;
        }

        if (preg_match('/[0-9]/', $mentah) !== 1) {
            return null;
        }

        $teks = strtolower($mentah);
        $faktor = 1;

        if (str_contains($teks, 'juta') || str_contains($teks, 'jt')) {
            $faktor = 1000000;
        } elseif (str_contains($teks, 'ribu') || str_contains($teks, 'rb') || preg_match('/[0-9][ ]*k$/', $teks) === 1) {
            $faktor = 1000;
        }

        if ($faktor === 1000000) {
            // Angka desimal preceded: "1,5 juta" berarti 1.500.000
            $angka = str_replace(',', '.', trim(preg_replace('/[a-z]+/', '', $teks)));
            $hasil = (int) round(((float) $angka) * 1000000);
        } else {
            $angka = preg_replace('/[^0-9]/', '', $teks);

            if ($angka === null || $angka === '') {
                return null;
            }

            $hasil = (int) $angka * $faktor;
        }

        if (str_contains($mentah, '-')) {
            return -1 * abs($hasil);
        }

        return $hasil;
    }

    /**
     * Mengubah nominal alokasi menjadi persentase yang selalu berjumlah 100.
     * Memakai perhitungan yang sama dengan Keuangan::persenAlokasi() supaya
     * form Setup Anggaran dan halaman lain tidak pernah berbeda.
     */
    public static function hitungPersen(int $kebutuhan, int $tabungan, int $hiburan): array
    {
        $semuaNol = $kebutuhan <= 0 && $tabungan <= 0 && $hiburan <= 0;

        if ($semuaNol) {
            return ['kebutuhan' => 50, 'tabungan' => 30, 'hiburan' => 20];
        }

        $sementara = new Keuangan([
            'pengeluaran_wajib' => $kebutuhan,
            'target_tabungan' => $tabungan,
            'budget_hiburan' => $hiburan,
        ]);

        return $sementara->persenAlokasi();
    }
}
