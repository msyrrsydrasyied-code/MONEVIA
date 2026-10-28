<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    use HasFactory;

    // Nama tabel di database
    protected $table = 'transaksi';

    // Kolom yang boleh diisi
    protected $fillable = [
        'user_id',
        'kategori_id',
        'nama',
        'jumlah',
        'jenis',
        'tanggal',
        'keterangan',
    ];

    // Relasi ke Model Kategori: Satu transaksi menggunakan satu kategori
    public function kategori()
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

