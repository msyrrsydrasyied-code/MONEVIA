<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $table = 'notifications';

    protected $fillable = [
        'user_id',
        'judul',
        'pesan',
        'tipe',
        'dibaca',
    ];

    protected $casts = [
        'dibaca' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeUnread($query)
    {
        return $query->where('dibaca', false);
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function markAsRead()
    {
        $this->update(['dibaca' => true]);
    }

    public static function createNotification($userId, $judul, $pesan, $tipe = 'info')
    {
        return self::create([
            'user_id' => $userId,
            'judul' => $judul,
            'pesan' => $pesan,
            'tipe' => $tipe,
        ]);
    }

    /**
     * Membuat notifikasi hanya bila belum ada notifikasi dengan judul yang sama
     * untuk user tersebut pada hari ini.
     *
     * Dipakai untuk pengingat otomatis (batas harian, sisa uang menipis) supaya
     * user tidak dibanjiri notifikasi yang isinya sama setiap menambah transaksi.
     * Tidak mengubah struktur database: leverages kolom judul dan created_at.
     */
    public static function buatSekaliPerHari($userId, $judul, $pesan, $tipe = 'info')
    {
        $sudahAda = self::where('user_id', $userId)
            ->where('judul', $judul)
            ->whereDate('created_at', now()->toDateString())
            ->exists();

        if ($sudahAda) {
            return null;
        }

        return self::createNotification($userId, $judul, $pesan, $tipe);
    }
}