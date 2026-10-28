@extends('layouts.app')

@section('title', 'Laporan — Monevia')

@section('content')
{{-- Header resmi khusus cetak. Aturan @media print dipusatkan di monevia.css --}}
<div class="print-only print-header">
    <div class="print-header-row">
        <div>
            <h1>MONEVIA</h1>
            <p>Personal Money Coach — Laporan Keuangan</p>
        </div>
        <div class="print-header-meta">
            <strong>Pengguna:</strong> {{ Auth::user()->name }} ({{ Auth::user()->email }})<br>
            <strong>Dicetak:</strong> {{ date('d-m-Y H:i') }}
        </div>
    </div>
    <p class="print-header-periode"><strong>Periode laporan:</strong> {{ $labelPeriode }}</p>
</div>

<div class="page-head fade-up no-print">
    <div class="page-head-row">
        <div>
            <h2>Laporan keuangan</h2>
            <p>Ringkasan uang masuk, uang keluar, dan sisa uangmu per periode.</p>
        </div>
        <div class="page-head-actions">
            <button type="button" onclick="window.print()" class="btn btn-primary">🖨️ Cetak</button>
        </div>
    </div>
</div>

@if($errors->any())
    <div class="alert-warning no-print">
        <ul class="alert-list">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<!-- FILTER PERIODE -->
<div class="card filter-card fade-up-delay-1 no-print">
    <h3 class="section-title"><span class="icon">🗓️</span> Pilih periode</h3>

    <form action="{{ url('/laporan') }}" method="GET">
        <div class="filter-grid">
            <div class="filter-field">
                <label for="select_periode" class="filter-label">Periode</label>
                <select id="select_periode" name="periode" onchange="toggleLaporanDate(this.value)">
                    <option value="hari_ini" {{ $periode == 'hari_ini' ? 'selected' : '' }}>Hari ini</option>
                    <option value="minggu_ini" {{ $periode == 'minggu_ini' ? 'selected' : '' }}>Minggu ini</option>
                    <option value="bulan_ini" {{ $periode == 'bulan_ini' ? 'selected' : '' }}>Bulan ini</option>
                    <option value="tahun_ini" {{ $periode == 'tahun_ini' ? 'selected' : '' }}>Tahun ini</option>
                    <option value="custom" {{ $periode == 'custom' ? 'selected' : '' }}>Rentang tanggal</option>
                </select>
            </div>

            <div class="filter-field">
                <label for="filter_jenis" class="filter-label">Jenis</label>
                <select id="filter_jenis" name="jenis">
                    <option value="semua" {{ $jenisFilter == 'semua' ? 'selected' : '' }}>Semua jenis</option>
                    <option value="pemasukan" {{ $jenisFilter == 'pemasukan' ? 'selected' : '' }}>Pemasukan</option>
                    <option value="pengeluaran" {{ $jenisFilter == 'pengeluaran' ? 'selected' : '' }}>Pengeluaran</option>
                </select>
            </div>

            <div class="filter-field">
                <label for="filter_kategori" class="filter-label">Kategori</label>
                <select id="filter_kategori" name="kategori_id">
                    <option value="semua" {{ $kategoriFilter == 'semua' ? 'selected' : '' }}>Semua kategori</option>
                    @foreach($kategoriList as $k)
                        <option value="{{ $k->id }}" {{ $kategoriFilter == $k->id ? 'selected' : '' }}>{{ $k->nama_kategori }}</option>
                    @endforeach
                </select>
            </div>

            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">Tampilkan</button>
                <a href="{{ url('/laporan') }}" class="btn btn-secondary">Reset</a>
            </div>
        </div>

        <div class="filter-extra" id="container_custom_date" @if($periode != 'custom') hidden @endif>
            <div class="filter-field filter-field-grow">
                <label for="dari_tanggal" class="filter-label">Dari tanggal</label>
                <input type="date" id="dari_tanggal" name="dari_tanggal" value="{{ $dariTanggal }}">
            </div>
            <div class="filter-field filter-field-grow">
                <label for="sampai_tanggal" class="filter-label">Sampai tanggal</label>
                <input type="date" id="sampai_tanggal" name="sampai_tanggal" value="{{ $sampaiTanggal }}">
            </div>
        </div>
    </form>
</div>

<!-- INFO PERIODE -->
<div class="row-item is-highlight mb-18 fade-up-delay-1">
    <div>
        <span class="row-label">Periode laporan</span>
        <span class="row-value">{{ $labelPeriode }}</span>
    </div>
</div>

<!-- RINGKASAN -->
<div class="grid-4 mb-18">
    <div class="metric is-green">
        <span class="metric-label">Total pemasukan</span>
        <h3 class="metric-value">Rp {{ number_format($totalPemasukan, 0, ',', '.') }}</h3>
        <small class="metric-sub">{{ $jumlahPemasukan }} transaksi</small>
    </div>
    <div class="metric is-red">
        <span class="metric-label">Total pengeluaran</span>
        <h3 class="metric-value">Rp {{ number_format($totalPengeluaran, 0, ',', '.') }}</h3>
        <small class="metric-sub">{{ $jumlahPengeluaran }} transaksi</small>
    </div>
    <div class="metric is-blue">
        <span class="metric-label">Total tabungan</span>
        <h3 class="metric-value">Rp {{ number_format($totalTabungan, 0, ',', '.') }}</h3>
        <small class="metric-sub">Terkumpul di target</small>
    </div>
    <div class="metric is-purple">
        <span class="metric-label">Sisa uang</span>
        <h3 class="metric-value">Rp {{ number_format($saldoReal, 0, ',', '.') }}</h3>
        <small class="metric-sub">Uang yang masih tersedia</small>
    </div>
</div>

<div class="grid-2 mb-18">
    <!-- PENGELUARAN PER KATEGORI -->
    <div class="card">
        <h3 class="section-title"><span class="icon">🛍️</span> Pengeluaran per kategori</h3>

        @if(count($kategoriPengeluaran) > 0)
            @foreach($kategoriPengeluaran as $kat)
                <div class="alloc-row">
                    <div class="alloc-row-head">
                        <span class="alloc-row-title">{{ $kat['nama'] }}</span>
                        <span class="alloc-row-value">Rp {{ number_format($kat['total'], 0, ',', '.') }} · {{ $kat['persentase'] }}%</span>
                    </div>
                    <div class="progress-bar-bg">
                        <div class="progress-bar-fill is-red" style="width: {{ $kat['persentase'] }}%;"></div>
                    </div>
                </div>
            @endforeach
        @else
            <div class="empty-state is-compact">
                <div class="empty-state-icon">🧾</div>
                <h4 class="empty-state-title">Belum ada pengeluaran</h4>
                <p class="empty-state-text mb-0">Tidak ada pengeluaran pada periode ini.</p>
            </div>
        @endif
    </div>

    <!-- RINGKASAN PER JENIS -->
    <div class="card">
        <h3 class="section-title"><span class="icon">📊</span> Ringkasan periode</h3>

        @php
            $selisihPeriode = $totalPemasukan - $totalPengeluaran;
        @endphp
        <div class="row-list">
            <div class="row-item">
                <div>
                    <span class="row-label">🟢 Pemasukan</span>
                    <span class="row-value">{{ $jumlahPemasukan }} transaksi</span>
                </div>
                <div class="row-tail text-green">+ Rp {{ number_format($totalPemasukan, 0, ',', '.') }}</div>
            </div>

            <div class="row-item">
                <div>
                    <span class="row-label">🔴 Pengeluaran</span>
                    <span class="row-value">{{ $jumlahPengeluaran }} transaksi</span>
                </div>
                <div class="row-tail text-red">− Rp {{ number_format($totalPengeluaran, 0, ',', '.') }}</div>
            </div>

            <div class="row-item is-highlight">
                <div>
                    <span class="row-label">Selisih periode ini</span>
                    <span class="row-value">Masuk dikurangi keluar</span>
                </div>
                <div class="row-tail {{ $selisihPeriode >= 0 ? 'text-green' : 'text-red' }}">
                    {{ $selisihPeriode >= 0 ? '+' : '−' }} Rp {{ number_format(abs($selisihPeriode), 0, ',', '.') }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- RINCIAN TRANSAKSI -->
<div class="card table-card">
    <div class="table-card-head">
        <h3>Rincian transaksi</h3>
        <span class="help">{{ $transaksi->count() }} transaksi pada {{ $labelPeriode }}</span>
    </div>

    @if($transaksi->isEmpty())
        <div class="p-22">
            <div class="empty-state">
                <div class="empty-state-icon">📑</div>
                <h4 class="empty-state-title">Belum ada transaksi</h4>
                <p class="empty-state-text">Tidak ada transaksi pada periode ini. Coba pilih periode lain.</p>
                <a href="{{ url('/laporan') }}" class="btn btn-secondary">Ganti periode</a>
            </div>
        </div>
    @else
        <div class="table-card-body">
            <table class="data">
                <thead>
                    <tr>
                        <th class="num">No</th>
                        <th>Tanggal</th>
                        <th>Transaksi</th>
                        <th>Kategori</th>
                        <th>Jenis</th>
                        <th class="num">Jumlah</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transaksi as $index => $t)
                        <tr>
                            <td class="num">{{ $index + 1 }}</td>
                            <td>{{ date('d M Y', strtotime($t->tanggal)) }}</td>
                            <td><strong>{{ $t->nama }}</strong></td>
                            <td>{{ $t->kategori ? $t->kategori->nama_kategori : '-' }}</td>
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
                            <td>{{ $t->keterangan ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    function toggleLaporanDate(value) {
        document.getElementById('container_custom_date').hidden = value !== 'custom';
    }
</script>
@endpush
