@extends('layouts.admin')

@section('title', 'Admin — Kategori')

@section('content')
<div class="page-head fade-up">
    <h2>Kategori transaksi</h2>
    <p>Kategori dipakai klien saat mencatat pemasukan dan pengeluaran.</p>
</div>

<div class="admin-split">
    <!-- FORM TAMBAH KATEGORI -->
    <div class="card fade-up-delay-1">
        <h3 class="section-title"><span class="icon">➕</span> Tambah kategori</h3>
        <form method="POST" action="{{ url('/admin/kategori') }}">
            @csrf
            <div class="form-group">
                <label for="nama_kategori">Nama kategori</label>
                <input id="nama_kategori" type="text" name="nama_kategori" placeholder="Contoh: Kos / Makanan" required>
                @error('nama_kategori')<span class="text-danger">{{ $message }}</span>@enderror
            </div>
            <button class="btn btn-primary btn-block" type="submit">Tambah kategori</button>
        </form>
    </div>

    <!-- TABEL KATEGORI -->
    <div class="card table-card fade-up-delay-2">
        <div class="table-card-head">
            <h3>Daftar kategori</h3>
            <span class="admin-badge-soft is-muted">{{ $kategori->count() }} kategori</span>
        </div>

        @if($kategori->isEmpty())
            <div style="padding: 22px;">
                <div class="empty-state is-compact">
                    <div class="empty-state-icon">🏷️</div>
                    <h4 class="empty-state-title">Belum ada kategori</h4>
                    <p class="empty-state-text">Tambahkan kategori pertama lewat form di samping.</p>
                </div>
            </div>
        @else
            <div class="table-card-body">
                <table class="data">
                    <thead>
                        <tr>
                            <th>Nama kategori</th>
                            <th class="num-cell">Dipakai</th>
                            <th class="aksi-col">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($kategori as $k)
                        <tr>
                            <td>
                                <form method="POST" action="{{ url('/admin/kategori/'.$k->id) }}" class="inline-edit">
                                    @csrf
                                    @method('PUT')
                                    <input type="text" name="nama_kategori" value="{{ $k->nama_kategori }}"
                                           aria-label="Nama kategori {{ $k->nama_kategori }}">
                                    <button class="btn btn-edit" type="submit">Simpan</button>
                                </form>
                            </td>
                            <td class="num-cell">{{ $k->transaksi_count }}</td>
                            <td class="aksi-col">
                                <form method="POST" action="{{ url('/admin/kategori/'.$k->id) }}"
                                      onsubmit="return confirm('Hapus kategori {{ $k->nama_kategori }}? Tindakan ini tidak bisa dibatalkan.')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-hapus" type="submit">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
