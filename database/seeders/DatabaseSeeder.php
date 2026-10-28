<?php

namespace Database\Seeders;

use App\Models\Kategori;
use App\Models\Keuangan;
use App\Models\TargetTabungan;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $kategoriList = [
            'Makanan',
            'Transportasi',
            'Pendidikan',
            'Hiburan',
            'Belanja',
            'Tabungan',
            'Lainnya',
        ];

        foreach ($kategoriList as $kategori) {
            Kategori::firstOrCreate(['nama_kategori' => $kategori]);
        }

        $admin = User::updateOrCreate(
            ['email' => 'admin@monevia.test'],
            [
                'name' => 'Admin Monevia',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        $client = User::updateOrCreate(
            ['email' => 'client@monevia.test'],
            [
                'name' => 'Alya Klien',
                'password' => Hash::make('password'),
                'role' => 'user',
            ]
        );

        Keuangan::updateOrCreate(
            ['user_id' => $client->id],
            [
                'jumlah_uang' => 1500000,
                'pengeluaran_wajib' => 600000,
                'target_tabungan' => 400000,
                'budget_hiburan' => 250000,
                'target_hari' => 30,
                'tanggal_mulai_target' => now()->subDays(3)->toDateString(),
            ]
        );

        if (Transaksi::where('user_id', $client->id)->count() === 0) {
            $makanan = Kategori::where('nama_kategori', 'Makanan')->first();
            $hiburan = Kategori::where('nama_kategori', 'Hiburan')->first();
            $lainnya = Kategori::where('nama_kategori', 'Lainnya')->first();

            Transaksi::create([
                'user_id' => $client->id,
                'kategori_id' => $lainnya->id,
                'nama' => 'Uang saku bulanan',
                'jumlah' => 1500000,
                'jenis' => 'pemasukan',
                'tanggal' => now()->subDays(5)->toDateString(),
                'keterangan' => 'Dari orang tua',
            ]);
            Transaksi::create([
                'user_id' => $client->id,
                'kategori_id' => $makanan->id,
                'nama' => 'Makan siang',
                'jumlah' => 22000,
                'jenis' => 'pengeluaran',
                'tanggal' => now()->toDateString(),
                'keterangan' => 'Kantin kampus',
            ]);
            Transaksi::create([
                'user_id' => $client->id,
                'kategori_id' => $hiburan->id,
                'nama' => 'Kopi dengan teman',
                'jumlah' => 18000,
                'jenis' => 'pengeluaran',
                'tanggal' => now()->subDay()->toDateString(),
            ]);
        }

        TargetTabungan::firstOrCreate(
            ['user_id' => $client->id, 'nama_target' => 'Laptop kuliah'],
            [
                'jumlah_target' => 4000000,
                'tabungan_saat_ini' => 750000,
                'target_tanggal' => now()->addMonths(6)->toDateString(),
            ]
        );
    }
}
