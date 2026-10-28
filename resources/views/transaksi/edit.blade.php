@extends('layouts.app')

@section('title', 'Edit Transaksi — Monevia')

@section('content')
<div class="page-head fade-up">
    <h2>Edit transaksi</h2>
    <p>Perbarui data yang salah tanpa menghapus catatanmu.</p>
</div>
<div class="card form-narrow fade-up-delay-1">
    <form action="{{ url('/transaksi/update/' . $transaksi->id) }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="nama">Nama transaksi</label>
            <input type="text" id="nama" name="nama" value="{{ old('nama', $transaksi->nama) }}">
            @error('nama')<span class="text-danger">{{ $message }}</span>@enderror
        </div>
        <div class="form-group">
            <label for="jumlah">Jumlah (Rp)</label>
            <input type="number" id="jumlah" name="jumlah" value="{{ old('jumlah', $transaksi->jumlah) }}" min="1">
            @error('jumlah')<span class="text-danger">{{ $message }}</span>@enderror
        </div>
        <div class="grid-2">
            <div class="form-group">
                <label for="jenis">Jenis</label>
                <select id="jenis" name="jenis">
                    <option value="pemasukan" {{ old('jenis', $transaksi->jenis) == 'pemasukan' ? 'selected' : '' }}>Pemasukan</option>
                    <option value="pengeluaran" {{ old('jenis', $transaksi->jenis) == 'pengeluaran' ? 'selected' : '' }}>Pengeluaran</option>
                </select>
                @error('jenis')<span class="text-danger">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="kategori_id">Kategori</label>
                <select id="kategori_id" name="kategori_id">
                    @foreach($kategori as $k)
                        <option value="{{ $k->id }}" {{ old('kategori_id', $transaksi->kategori_id) == $k->id ? 'selected' : '' }}>{{ $k->nama_kategori }}</option>
                    @endforeach
                </select>
                @error('kategori_id')<span class="text-danger">{{ $message }}</span>@enderror
            </div>
        </div>
        <div class="form-group">
            <label for="tanggal">Tanggal</label>
            <input type="date" id="tanggal" name="tanggal" value="{{ old('tanggal', $transaksi->tanggal) }}">
            @error('tanggal')<span class="text-danger">{{ $message }}</span>@enderror
        </div>
        <div class="form-group">
            <label for="keterangan">Keterangan (opsional)</label>
            <textarea id="keterangan" name="keterangan" rows="3">{{ old('keterangan', $transaksi->keterangan) }}</textarea>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Simpan perubahan</button>
            <a href="{{ url('/transaksi') }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>
@endsection
