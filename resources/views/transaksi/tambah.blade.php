@extends('layouts.app')

@section('title', 'Tambah Transaksi — Monevia')

@section('content')
<div class="page-head fade-up">
    <h2>Tambah transaksi</h2>
    <p>Catat uang masuk atau keluar. Isi yang sederhana saja, sisanya opsional.</p>
</div>
<div class="card form-narrow fade-up-delay-1">
    <form action="{{ url('/transaksi/simpan') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="nama">Nama transaksi</label>
            <input type="text" id="nama" name="nama" value="{{ old('nama') }}" placeholder="Contoh: Uang saku / Makan siang">
            @error('nama')<span class="text-danger">{{ $message }}</span>@enderror
        </div>
        <div class="form-group">
            <label for="jumlah">Jumlah (Rp)</label>
            <input type="number" id="jumlah" name="jumlah" value="{{ old('jumlah') }}" placeholder="50000" min="1">
            @error('jumlah')<span class="text-danger">{{ $message }}</span>@enderror
        </div>
        <div class="grid-2">
            <div class="form-group">
                <label for="jenis">Jenis</label>
                <select id="jenis" name="jenis">
                    <option value="">Pilih jenis</option>
                    <option value="pemasukan" {{ old('jenis') == 'pemasukan' ? 'selected' : '' }}>Pemasukan</option>
                    <option value="pengeluaran" {{ old('jenis') == 'pengeluaran' ? 'selected' : '' }}>Pengeluaran</option>
                </select>
                @error('jenis')<span class="text-danger">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="kategori_id">Kategori</label>
                <select id="kategori_id" name="kategori_id">
                    <option value="">Pilih kategori</option>
                    @foreach($kategori as $k)
                        <option value="{{ $k->id }}" {{ old('kategori_id') == $k->id ? 'selected' : '' }}>{{ $k->nama_kategori }}</option>
                    @endforeach
                </select>
                @error('kategori_id')<span class="text-danger">{{ $message }}</span>@enderror
            </div>
        </div>
        <div class="form-group">
            <label for="tanggal">Tanggal</label>
            <input type="date" id="tanggal" name="tanggal" value="{{ old('tanggal', date('Y-m-d')) }}">
            @error('tanggal')<span class="text-danger">{{ $message }}</span>@enderror
        </div>
        <div class="form-group">
            <label for="keterangan">Keterangan (opsional)</label>
            <textarea id="keterangan" name="keterangan" rows="3" placeholder="Catatan singkat">{{ old('keterangan') }}</textarea>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Simpan transaksi</button>
            <a href="{{ url('/transaksi') }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>
@endsection
