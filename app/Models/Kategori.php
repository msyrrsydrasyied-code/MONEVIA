<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kategori extends Model
{
    use HasFactory;

    // Nama tabel di database
    protected $table = 'kategori';

    // Kolom yang boleh diisi secara masal (mass assignment)
    protected $fillable = [
        'nama_kategori',
    ];

    public function transaksi()
    {
        return $this->hasMany(Transaksi::class, 'kategori_id');
    }
}

