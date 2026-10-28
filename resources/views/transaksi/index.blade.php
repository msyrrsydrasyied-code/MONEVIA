@extends('layouts.app')

@section('title', 'Monevia - Daftar Transaksi')

@section('content')
<div class="page-head fade-up">
    <h2>Transaksi</h2>
    <p>Semua catatan uang masuk dan uang keluar kamu ada di sini.</p>
</div>

@if($errors->any())
    <div class="alert-danger">
        <strong>Ada yang perlu diperbaiki:</strong>
        <ul class="alert-list">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<!-- RINGKASAN -->
<div class="grid-4 mb-18">
    <div class="metric is-green">
        <span class="metric-label">Pemasukan</span>
        <h3 class="metric-value">Rp {{ number_format($totalPemasukan, 0, ',', '.') }}</h3>
        <small class="metric-sub">Dari hasil filter aktif</small>
    </div>
    <div class="metric is-red">
        <span class="metric-label">Pengeluaran</span>
        <h3 class="metric-value">Rp {{ number_format($totalPengeluaran, 0, ',', '.') }}</h3>
        <small class="metric-sub">Dari hasil filter aktif</small>
    </div>
    <div class="metric is-blue">
        <span class="metric-label">Jumlah transaksi</span>
        <h3 class="metric-value">{{ $totalTransaksi }}</h3>
        <small class="metric-sub">Transaksi yang cocok</small>
    </div>
    <div class="metric {{ ($totalPemasukan - $totalPengeluaran) >= 0 ? 'is-purple' : 'is-gold' }}">
        <span class="metric-label">Selisih</span>
        <h3 class="metric-value">{{ ($totalPemasukan - $totalPengeluaran) >= 0 ? '+' : '−' }} Rp {{ number_format(abs($totalPemasukan - $totalPengeluaran), 0, ',', '.') }}</h3>
        <small class="metric-sub">Masuk dikurangi keluar</small>
    </div>
</div>

<!-- PENCARIAN & FILTER -->
<div class="card filter-card fade-up-delay-1">
    <form action="{{ url('/transaksi') }}" method="GET">
        <div class="form-group">
            <label for="search" class="filter-label">Cari transaksi</label>
            <input type="text" id="search" name="search" value="{{ request('search') }}"
                   placeholder="Ketik nama transaksi atau keterangan...">
        </div>

        <div class="filter-grid">
            <div class="filter-field">
                <label for="filter_jenis" class="filter-label">Jenis</label>
                <select id="filter_jenis" name="jenis">
                    <option value="semua" {{ request('jenis') == 'semua' || !request('jenis') ? 'selected' : '' }}>Semua jenis</option>
                    <option value="pemasukan" {{ request('jenis') == 'pemasukan' ? 'selected' : '' }}>Pemasukan</option>
                    <option value="pengeluaran" {{ request('jenis') == 'pengeluaran' ? 'selected' : '' }}>Pengeluaran</option>
                </select>
            </div>

            <div class="filter-field">
                <label for="filter_kategori" class="filter-label">Kategori</label>
                <select id="filter_kategori" name="kategori_id">
                    <option value="semua" {{ request('kategori_id') == 'semua' || !request('kategori_id') ? 'selected' : '' }}>Semua kategori</option>
                    @foreach($kategori as $k)
                        <option value="{{ $k->id }}" {{ request('kategori_id') == $k->id ? 'selected' : '' }}>{{ $k->nama_kategori }}</option>
                    @endforeach
                </select>
            </div>

            <div class="filter-field">
                <label for="filter_tanggal_select" class="filter-label">Periode</label>
                <select id="filter_tanggal_select" name="filter_tanggal" onchange="toggleTransaksiDate(this.value)">
                    <option value="semua" {{ request('filter_tanggal') == 'semua' || !request('filter_tanggal') ? 'selected' : '' }}>Semua tanggal</option>
                    <option value="hari_ini" {{ request('filter_tanggal') == 'hari_ini' ? 'selected' : '' }}>Hari ini</option>
                    <option value="minggu_ini" {{ request('filter_tanggal') == 'minggu_ini' ? 'selected' : '' }}>Minggu ini</option>
                    <option value="bulan_ini" {{ request('filter_tanggal') == 'bulan_ini' ? 'selected' : '' }}>Bulan ini</option>
                    <option value="custom" {{ request('filter_tanggal') == 'custom' ? 'selected' : '' }}>Rentang tanggal</option>
                </select>
            </div>

            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">Terapkan</button>
                <a href="{{ url('/transaksi') }}" class="btn btn-secondary">Reset</a>
            </div>
        </div>

        <div class="filter-extra" id="custom_date_container" @if(request('filter_tanggal') != 'custom') hidden @endif>
            <div class="filter-field filter-field-grow">
                <label for="dari_tanggal" class="filter-label">Dari tanggal</label>
                <input type="date" id="dari_tanggal" name="dari_tanggal" value="{{ request('dari_tanggal') }}">
            </div>
            <div class="filter-field filter-field-grow">
                <label for="sampai_tanggal" class="filter-label">Sampai tanggal</label>
                <input type="date" id="sampai_tanggal" name="sampai_tanggal" value="{{ request('sampai_tanggal') }}">
            </div>
        </div>
    </form>
</div>

<!-- TABEL TRANSAKSI -->
<div class="card table-card fade-up-delay-2">
    <div class="table-card-head">
        <h3>Daftar transaksi</h3>
        <a href="{{ url('/transaksi/tambah') }}" class="btn btn-primary">+ Tambah</a>
    </div>

    @if($transaksi->isEmpty())
        <div class="p-22">
            <div class="empty-state">
                <div class="empty-state-icon">🔍</div>
                <h4 class="empty-state-title">Transaksi tidak ditemukan</h4>
                <p class="empty-state-text">
                    @if(request('search') || request('jenis') || request('kategori_id') || request('filter_tanggal'))
                        Coba ubah kata kunci atau atur ulang filternya.
                    @else
                        Belum ada transaksi. Mulai catat uang masuk atau keluarmu.
                    @endif
                </p>
                @if(request('search') || request('jenis') || request('kategori_id') || request('filter_tanggal'))
                    <a href="{{ url('/transaksi') }}" class="btn btn-secondary">Reset filter</a>
                @else
                    <a href="{{ url('/transaksi/tambah') }}" class="btn btn-primary">+ Tambah transaksi</a>
                @endif
            </div>
        </div>
    @else
        <div class="table-card-body">
            <table class="data">
                <thead>
                    <tr>
                        <th class="num">No</th>
                        <th>Transaksi</th>
                        <th>Kategori</th>
                        <th>Tanggal</th>
                        <th>Jenis</th>
                        <th class="num">Jumlah</th>
                        <th class="aksi">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transaksi as $index => $t)
                        <tr>
                            <td class="num">{{ $transaksi->firstItem() + $index }}</td>
                            <td>
                                <strong>{{ $t->nama }}</strong>
                                @if($t->keterangan)
                                    <div class="help">{{ $t->keterangan }}</div>
                                @endif
                            </td>
                            <td>{{ $t->kategori ? $t->kategori->nama_kategori : '-' }}</td>
                            <td>{{ date('d M Y', strtotime($t->tanggal)) }}</td>
                            <td>
                                <span class="badge {{ $t->jenis == 'pemasukan' ? 'badge-pemasukan' : 'badge-pengeluaran' }}">
                                    {{ $t->jenis }}
                                </span>
                            </td>
                            <td class="num">
                                <strong class="{{ $t->jenis == 'pemasukan' ? 'text-green' : 'text-red' }}">
                                    {{ $t->jenis == 'pemasukan' ? '+' : '−' }} Rp {{ number_format($t->jumlah, 0, ',', '.') }}
                                </strong>
                            </td>
                            <td class="aksi">
                                <div class="table-actions">
                                    <a href="{{ url('/transaksi/edit/' . $t->id) }}" class="btn btn-edit">Edit</a>
                                    <form action="{{ url('/transaksi/hapus/' . $t->id) }}" method="POST" class="form-inline"
                                          onsubmit="return confirm('Yakin ingin menghapus transaksi &quot;{{ $t->nama }}&quot;? Tindakan ini tidak bisa dibatalkan.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-hapus">Hapus</button>
                                    </form>
                                </div>
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

@push('scripts')
<script>
    function toggleTransaksiDate(value) {
        document.getElementById('custom_date_container').hidden = value !== 'custom';
    }
</script>
@endpush
