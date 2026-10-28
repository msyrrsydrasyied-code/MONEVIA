@extends('layouts.admin')

@section('title', 'Admin — Transaksi')

@section('content')
<div class="page-head fade-up">
    <div class="page-head-row">
        <div>
            <h2>Semua transaksi</h2>
            <p>Lihat catatan keuangan seluruh pengguna. Data bersifat hanya-baca di halaman ini.</p>
        </div>
    </div>
</div>

<div class="card table-card fade-up-delay-1">
    <div class="table-card-head">
        <h3>Daftar transaksi</h3>
        <form method="GET" action="{{ url('/admin/transaksi') }}" class="admin-search">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama transaksi atau pengguna" aria-label="Cari transaksi">
            <button class="btn btn-secondary" type="submit">Cari</button>
            @if(request('q'))
                <a href="{{ url('/admin/transaksi') }}" class="btn btn-secondary">Reset</a>
            @endif
        </form>
    </div>

    @if($transaksi->isEmpty())
        <div style="padding: 22px;">
            <div class="empty-state is-compact">
                <div class="empty-state-icon">🧾</div>
                <h4 class="empty-state-title">Transaksi tidak ditemukan</h4>
                <p class="empty-state-text">
                    @if(request('q'))
                        Tidak ada transaksi yang cocok dengan pencarianmu.
                    @else
                        Belum ada transaksi yang tercatat di aplikasi.
                    @endif
                </p>
                @if(request('q'))
                    <a href="{{ url('/admin/transaksi') }}" class="btn btn-secondary">Reset pencarian</a>
                @endif
            </div>
        </div>
    @else
        <div class="table-card-body">
            <table class="data">
                <thead>
                    <tr>
                        <th>Pengguna</th>
                        <th>Transaksi</th>
                        <th>Jenis</th>
                        <th>Kategori</th>
                        <th>Tanggal</th>
                        <th class="num-cell">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($transaksi as $t)
                    <tr>
                        <td>
                            <div class="user-cell">
                                <div class="avatar">{{ strtoupper(substr(optional($t->user)->name ?? '?', 0, 1)) }}</div>
                                <span>{{ optional($t->user)->name ?? 'Tanpa pengguna' }}</span>
                            </div>
                        </td>
                        <td><strong>{{ $t->nama }}</strong></td>
                        <td>
                            <span class="badge {{ $t->jenis == 'pemasukan' ? 'badge-pemasukan' : 'badge-pengeluaran' }}">
                                {{ $t->jenis == 'pemasukan' ? 'Pemasukan' : 'Pengeluaran' }}
                            </span>
                        </td>
                        <td>{{ optional($t->kategori)->nama_kategori ?? '-' }}</td>
                        <td>{{ date('d M Y', strtotime($t->tanggal)) }}</td>
                        <td class="num-cell">
                            <strong class="{{ $t->jenis == 'pemasukan' ? 'text-green' : 'text-red' }}">
                                {{ $t->jenis == 'pemasukan' ? '+' : '−' }} Rp {{ number_format($t->jumlah, 0, ',', '.') }}
                            </strong>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="table-foot">
            <span>Menampilkan {{ $transaksi->firstItem() }}–{{ $transaksi->lastItem() }} dari {{ $transaksi->total() }} transaksi</span>
            {{ $transaksi->links('pagination::monevia') }}
        </div>
    @endif
</div>
@endsection
