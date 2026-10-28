<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('target_tabungan')) {
            Schema::create('target_tabungan', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->default(1);
                $table->string('nama_target');
                $table->decimal('jumlah_target', 15, 2);
                $table->decimal('tabungan_saat_ini', 15, 2)->default(0);
                $table->date('target_tanggal')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('target_tabungan');
    }
};
