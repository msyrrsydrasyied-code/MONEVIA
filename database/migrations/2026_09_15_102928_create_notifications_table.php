<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->default(1);
            $table->string('judul');
            $table->text('pesan');
            $table->string('tipe')->default('info'); // info, warning, success, danger
            $table->boolean('dibaca')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'dibaca']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};