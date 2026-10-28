<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tahap 4 Audit — dua perbaikan data yang additive:
 *
 * 1. Kolom `uang_disisihkan` untuk membedakan:
 *      - Target Tabungan = tujuan (tidak mengurangi saldo)
 *      - Uang yang benar-benar disisihkan = uang yang tidak bisa dibelanjakan
 *
 * 2. UNIQUE index pada `keuangan.user_id` agar satu user hanya punya satu
 *    baris keuangan. Sebelumnya `firstOrCreate`/`updateOrCreate` berpotensi
 *    membuat baris ganda bila dua request datang bersamaan.
 *
 * Migration ini hanya menambahkan kolom dan index. Tidak menghapus,
 * tidak mengubah, dan tidak me-reset data yang sudah ada.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('keuangan')) {
            return;
        }

        if (!Schema::hasColumn('keuangan', 'uang_disisihkan')) {
            Schema::table('keuangan', function (Blueprint $table) {
                $table->decimal('uang_disisihkan', 15, 2)->default(0)->after('budget_hiburan');
            });
        }

        // Jangan pernah gagal total bila ternyata ada user_id ganda:
        // data duplikat dilaporkan lewat exception agar ditangani manual,
        // bukan dihapus diam-diam.
        $duplikat = DB::table('keuangan')
            ->select('user_id', DB::raw('COUNT(*) as total'))
            ->groupBy('user_id')
            ->having('total', '>', 1)
            ->pluck('user_id')
            ->all();

        if ($duplikat !== []) {
            throw new \RuntimeException(
                'Migrasi dibatalkan: keuangan.user_id duplikat untuk user_id ' . implode(', ', $duplikat)
                . '. Bersihkan duplikat secara manual lalu jalankan ulang migrasi ini.'
            );
        }

        $sudahUnique = false;
        foreach (Schema::getIndexes('keuangan') as $index) {
            if ($index['columns'] === ['user_id'] && ($index['unique'] ?? false) === true) {
                $sudahUnique = true;
                break;
            }
        }

        if (!$sudahUnique) {
            Schema::table('keuangan', function (Blueprint $table) {
                $table->unique('user_id', 'keuangan_user_id_unique');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('keuangan')) {
            return;
        }

        if (Schema::hasColumn('keuangan', 'uang_disisihkan')) {
            Schema::table('keuangan', function (Blueprint $table) {
                $table->dropColumn('uang_disisihkan');
            });
        }

        Schema::table('keuangan', function (Blueprint $table) {
            $table->dropUnique('keuangan_user_id_unique');
        });
    }
};