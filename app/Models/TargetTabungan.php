<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TargetTabungan extends Model
{
    use HasFactory;

    // Nama tabel di database
    protected $table = 'target_tabungan';

    // Kolom yang boleh diisi
    protected $fillable = [
        'user_id',
        'nama_target',
        'jumlah_target',
        'tabungan_saat_ini',
        'target_tanggal',
    ];
}
