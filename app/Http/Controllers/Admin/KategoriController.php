<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use Illuminate\Http\Request;

class KategoriController extends Controller
{
    public function index()
    {
        $kategori = Kategori::withCount('transaksi')->orderBy('nama_kategori')->get();
        return view('admin.kategori.index', compact('kategori'));
    }

    public function store(Request $request)
    {
        $request->validate(['nama_kategori' => 'required|string|max:80|unique:kategori,nama_kategori']);
        Kategori::create(['nama_kategori' => $request->nama_kategori]);
        return back()->with('sukses', 'Kategori ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $kategori = Kategori::findOrFail($id);
        $request->validate(['nama_kategori' => 'required|string|max:80|unique:kategori,nama_kategori,' . $kategori->id]);
        $kategori->update(['nama_kategori' => $request->nama_kategori]);
        return back()->with('sukses', 'Kategori diperbarui.');
    }

    public function destroy($id)
    {
        $kategori = Kategori::findOrFail($id);
        if ($kategori->transaksi()->exists()) {
            return back()->withErrors(['kategori' => 'Kategori masih dipakai transaksi, tidak bisa dihapus.']);
        }
        $kategori->delete();
        return back()->with('sukses', 'Kategori dihapus.');
    }
}
