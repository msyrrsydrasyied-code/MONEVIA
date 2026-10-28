<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('transaksi')) {
            Schema::create('transaksi', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->default(1);
                $table->unsignedBigInteger('kategori_id');
                $table->string('nama');
                $table->decimal('jumlah', 15, 2);
                $table->enum('jenis', ['pemasukan', 'pengeluaran']);
                $table->date('tanggal');
                $table->text('keterangan')->nullable();
                $table->timestamps();

                // Foreign key relasi ke tabel kategori (opsional)
                $table->foreign('kategori_id')->references('id')->on('kategori')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi');
    }
};

