<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('keuangan')) {
            Schema::create('keuangan', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->default(1);
                $table->decimal('jumlah_uang', 15, 2)->default(0);
                $table->decimal('pengeluaran_wajib', 15, 2)->default(0);
                $table->decimal('target_tabungan', 15, 2)->default(0);
                $table->decimal('budget_hiburan', 15, 2)->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('keuangan');
    }
};
