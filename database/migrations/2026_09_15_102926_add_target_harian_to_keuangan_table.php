<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('keuangan', function (Blueprint $table) {
            $table->unsignedInteger('target_hari')->nullable()->after('budget_hiburan');
            $table->date('tanggal_mulai_target')->nullable()->after('target_hari');
        });
    }

    public function down(): void
    {
        Schema::table('keuangan', function (Blueprint $table) {
            $table->dropColumn(['target_hari', 'tanggal_mulai_target']);
        });
    }
};