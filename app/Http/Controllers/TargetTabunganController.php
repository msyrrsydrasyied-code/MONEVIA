<?php

namespace App\Http\Controllers;

use App\Models\TargetTabungan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TargetTabunganController extends Controller
{
    // Menampilkan semua target tabungan milik user login
    public function index()
    {
        $userId = Auth::id();
        $target = TargetTabungan::where('user_id', $userId)->orderBy('created_at', 'desc')->get();
        return view('target_tabungan.index', compact('target'));
    }

    // Menampilkan form tambah target tabungan
    public function tambah()
    {
        return view('target_tabungan.tambah');
    }

    // Menyimpan target tabungan baru milik user login
    public function simpan(Request $request)
    {
        $request->validate([
            'nama_target' => 'required',
            'jumlah_target' => 'required|numeric|min:1',
            'tabungan_saat_ini' => 'nullable|numeric|min:0',
            'target_tanggal' => 'nullable|date',
        ]);

        $userId = Auth::id();

        TargetTabungan::create([
            'user_id' => $userId,
            'nama_target' => $request->nama_target,
            'jumlah_target' => $request->jumlah_target,
            'tabungan_saat_ini' => $request->tabungan_saat_ini ?? 0,
            'target_tanggal' => $request->target_tanggal,
        ]);

        return redirect('/target-tabungan')->with('sukses', 'Target tabungan berhasil dibuat!');
    }

    // Menambah saldo pada target tabungan milik user login (dengan ownership check)
    public function updateSaldo(Request $request, $id)
    {
        $request->validate([
            'tambahan_saldo' => 'required|numeric|min:1',
        ]);

        $userId = Auth::id();
        $target = TargetTabungan::where('user_id', $userId)->findOrFail($id);

        $newSaldo = $target->tabungan_saat_ini + $request->tambahan_saldo;
        
        if ($newSaldo > $target->jumlah_target) {
            return back()->with('error', 'Saldo tidak boleh melebihi target tabungan (Rp ' . number_format($target->jumlah_target, 0, ',', '.') . ')!');
        }

        $target->tabungan_saat_ini = $newSaldo;
        $target->save();

        return redirect('/target-tabungan')->with('sukses', 'Saldo tabungan berhasil ditambahkan!');
    }

    // Menghapus target tabungan (dengan ownership check)
    public function hapus($id)
    {
        $userId = Auth::id();
        $target = TargetTabungan::where('user_id', $userId)->findOrFail($id);
        $target->delete();

        return redirect('/target-tabungan')->with('sukses', 'Target tabungan berhasil dihapus!');
    }
}
