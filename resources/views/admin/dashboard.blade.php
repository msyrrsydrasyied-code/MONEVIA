@extends('layouts.admin')

@section('title', 'Admin — Ringkasan')

@section('content')
<div class="page-head fade-up">
    <h2>Ringkasan admin</h2>
    <p>Pantau pengguna, transaksi, dan tabungan di seluruh aplikasi.</p>
</div>

<!-- KARTU STATISTIK -->
<div class="grid-4 mb-18 fade-up-delay-1">
    <div class="stat hero">
        <label>Pengguna klien</label>
        <h3>{{ $totalPengguna }}</h3>
        <small>{{ $totalAdmin }} admin · {{ $totalPengguna + $totalAdmin }} total akun</small>
    </div>
    <div class="metric is-blue">
        <span class="metric-label">Semua transaksi</span>
        <h3 class="metric-value">{{ $totalTransaksi }}</h3>
        <small class="metric-sub">Seluruh pengguna</small>
    </div>
    <div class="metric is-green">
        <span class="metric-label">Pemasukan</span>
        <h3 class="metric-value">Rp {{ number_format($totalPemasukan, 0, ',', '.') }}</h3>
        <small class="metric-sub">Total uang masuk</small>
    </div>
    <div class="metric is-red">
        <span class="metric-label">Pengeluaran</span>
        <h3 class="metric-value">Rp {{ number_format($totalPengeluaran, 0, ',', '.') }}</h3>
        <small class="metric-sub">Total uang keluar</small>
    </div>
</div>

<!-- RINGKASAN TABUNGAN -->
<div class="grid-2 mb-18 fade-up-delay-2">
    <div class="card mb-0">
        <h3 class="section-title"><span class="icon">💰</span> Total tabungan pengguna</h3>
        <div class="row-list">
            <div class="row-item is-highlight">
                <div>
                    <span class="row-label">Terkumpul di seluruh target</span>
                    <span class="row-value">Rp {{ number_format($totalTabungan, 0, ',', '.') }}</span>
                </div>
                <span class="admin-badge-soft">Tabungan</span>
            </div>
        </div>
    </div>

    <div class="card mb-0">
        <h3 class="section-title"><span class="icon">📊</span> Arus kas aplikasi</h3>
        <div class="row-list">
            @php $selisihAdmin = $totalPemasukan - $totalPengeluaran; @endphp
            <div class="row-item">
                <div>
                    <span class="row-label">Selisih pemasukan dan pengeluaran</span>
                    <span class="row-value">Masuk dikurangi keluar</span>
                </div>
                <span class="row-tail {{ $selisihAdmin >= 0 ? 'text-green' : 'text-red' }}">
                    {{ $selisihAdmin >= 0 ? '+' : '−' }} Rp {{ number_format(abs($selisihAdmin), 0, ',', '.') }}
                </span>
            </div>
            <div class="row-item">
                <div>
                    <span class="row-label">Pengeluaran rata-rata per catatan</span>
                    <span class="row-value">
                        Rp {{ number_format($totalTransaksi > 0 ? $totalPengeluaran / $totalTransaksi : 0, 0, ',', '.') }}
                    </span>
                </div>
                <span class="admin-badge-soft is-muted">Rata-rata</span>
            </div>
        </div>
    </div>
</div>

<div class="grid-2">
    <!-- PENGGUNA TERBARU -->
    <div class="card table-card mb-0">
        <div class="table-card-head">
            <h3>Pengguna terbaru</h3>
            <a href="{{ url('/admin/pengguna') }}" class="link-plain">Kelola pengguna</a>
        </div>
        <div class="table-card-body">
            <table class="data">
                <thead><tr><th>Nama</th><th>Peran</th><th class="num-cell">Bergabung</th></tr></thead>
                <tbody>
                @forelse($penggunaBaru as $u)
                    <tr>
                        <td>
                            <div class="user-cell">
                                <div class="avatar">{{ strtoupper(substr($u->name, 0, 1)) }}</div>
                                <div>
                                    <p>{{ $u->name }}</p>
                                    <span>{{ $u->email }}</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="admin-badge-soft {{ $u->role === 'admin' ? 'is-admin' : '' }}">
                                {{ $u->role === 'admin' ? 'Admin' : 'Klien' }}
                            </span>
                        </td>
                        <td class="num-cell">{{ $u->created_at->format('d M Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3"><div class="empty-state is-compact"><div class="empty-state-icon">👥</div><h4 class="empty-state-title">Belum ada pengguna</h4></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- TRANSAKSI TERBARU -->
    <div class="card table-card mb-0">
        <div class="table-card-head">
            <h3>Transaksi terbaru</h3>
            <a href="{{ url('/admin/transaksi') }}" class="link-plain">Lihat semua</a>
        </div>
        @if($transaksiTerakhir->isEmpty())
            <div style="padding: 22px;">
                <div class="empty-state is-compact">
                    <div class="empty-state-icon">🧾</div>
                    <h4 class="empty-state-title">Belum ada transaksi</h4>
                    <p class="empty-state-text">Transaksi dari seluruh pengguna akan muncul di sini.</p>
                </div>
            </div>
        @else
            <div class="table-card-body">
                <table class="data">
                    <thead><tr><th>Detail</th><th class="num-cell">Jumlah</th></tr></thead>
                    <tbody>
                    @foreach($transaksiTerakhir as $t)
                        <tr>
                            <td>
                                <strong>{{ $t->nama }}</strong>
                                <div class="help">
                                    {{ optional($t->user)->name ?? 'Tanpa pengguna' }} · {{ optional($t->kategori)->nama_kategori ?? '-' }}
                                </div>
                            </td>
                            <td class="num-cell">
                                <span class="badge {{ $t->jenis == 'pemasukan' ? 'badge-pemasukan' : 'badge-pengeluaran' }}">
                                    {{ $t->jenis == 'pemasukan' ? '+' : '−' }} Rp {{ number_format($t->jumlah, 0, ',', '.') }}
                                </span>
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
