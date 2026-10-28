@extends('layouts.app')

@section('title', 'Insight — Monevia')

@section('content')
<div class="page-head fade-up">
    <h2>Insight</h2>
    <p>Halo, {{ Auth::user()->name }}! Berikut ringkasan kebiasaan pengeluaranmu bulan ini.</p>
</div>

<!-- METRIK BULAN INI -->
<div class="grid-4 mb-18">
    <div class="metric is-green">
        <span class="metric-label">Pemasukan bulan ini</span>
        <h3 class="metric-value">Rp {{ number_format($totalPemasukanBulanIni, 0, ',', '.') }}</h3>
        <small class="metric-sub">Uang yang masuk</small>
    </div>
    <div class="metric is-red">
        <span class="metric-label">Pengeluaran bulan ini</span>
        <h3 class="metric-value">Rp {{ number_format($totalPengeluaranBulanIni, 0, ',', '.') }}</h3>
        <small class="metric-sub">{{ $jumlahTransaksiPengeluaranBulanIni }} transaksi keluar</small>
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
    <!-- INSIGHT MONEY COACH -->
    <div class="card fade-up-delay-1">
        <h3 class="section-title"><span class="icon">💡</span> Saran Money Coach</h3>

        @if(count($insights) > 0)
            <div class="insight-list">
                @foreach($insights as $item)
                    <div class="insight-item is-{{ $item['tipe'] }}">
                        <div class="insight-head">
                            <span>{{ $item['ikon'] }}</span>
                            <h4 class="insight-title">{{ $item['judul'] }}</h4>
                        </div>
                        <p class="insight-text">{{ $item['pesan'] }}</p>
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-state is-compact">
                <div class="empty-state-icon">💡</div>
                <h4 class="empty-state-title">Belum ada insight</h4>
                <p class="empty-state-text">Catat transaksi dulu supaya Monevia bisa menganalisis pengeluaranmu.</p>
            </div>
        @endif
    </div>

    <!-- ANALISIS PENGELUARAN -->
    <div class="card fade-up-delay-1">
        <h3 class="section-title"><span class="icon">📊</span> Analisis Pengeluaran Bulan Ini</h3>

        <div class="row-list">
            <div class="row-item">
                <div>
                    <span class="row-label">Kategori terbesar</span>
                    <span class="row-value">{{ $kategoriTerbesar ?? 'Belum ada' }}</span>
                </div>
                <div class="row-tail text-red">Rp {{ number_format($nominalKategoriTerbesar, 0, ',', '.') }}</div>
            </div>

            <div class="row-item">
                <div>
                    <span class="row-label">Paling sering dicatat</span>
                    <span class="row-value">{{ $kategoriTerbanyak ?? 'Belum ada' }}</span>
                </div>
                <div class="row-tail text-blue">{{ $frekuensiKategoriTerbanyak }} kali</div>
            </div>

            <div class="row-item">
                <div>
                    <span class="row-label">Rata-rata per transaksi</span>
                    <span class="row-value">Rp {{ number_format($rataRataPengeluaran, 0, ',', '.') }}</span>
                </div>
                <div class="row-tail text-muted">per transaksi</div>
            </div>

            <div class="row-item is-highlight">
                <div>
                    <span class="row-label">Jumlah pengeluaran bulan ini</span>
                    <span class="row-value">{{ $jumlahTransaksiPengeluaranBulanIni }} transaksi</span>
                </div>
                <div class="row-tail text-green">Rp {{ number_format($totalPengeluaranBulanIni, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>
</div>

<div class="grid-2">
    <!-- PERBANDINGAN BULANAN -->
    <div class="card fade-up-delay-2">
        <h3 class="section-title"><span class="icon">🔄</span> Bandingkan Bulan Ini vs Bulan Lalu</h3>

        <div class="row-list">
            <div class="row-item">
                <div>
                    <span class="row-label">Pengeluaran bulan lalu</span>
                    <span class="row-value">Rp {{ number_format($totalPengeluaranBulanLalu, 0, ',', '.') }}</span>
                </div>
            </div>

            <div class="row-item">
                <div>
                    <span class="row-label">Pengeluaran bulan ini</span>
                    <span class="row-value">Rp {{ number_format($totalPengeluaranBulanIni, 0, ',', '.') }}</span>
                </div>
            </div>

            @php
                $selisih = $totalPengeluaranBulanIni - $totalPengeluaranBulanLalu;
            @endphp
            <div class="row-item is-highlight">
                <div>
                    <span class="row-label">Perubahan</span>
                    <span class="row-value {{ $selisih <= 0 ? 'text-green' : 'text-red' }}">
                        @if($totalPengeluaranBulanLalu == 0)
                            Belum ada data pembanding
                        @elseif($selisih <= 0)
                            Lebih hemat Rp {{ number_format(abs($selisih), 0, ',', '.') }}
                        @else
                            Naik Rp {{ number_format($selisih, 0, ',', '.') }}
                        @endif
                    </span>
                </div>
                @if($totalPengeluaranBulanLalu > 0)
                    <span class="badge {{ $selisih <= 0 ? 'badge-pemasukan' : 'badge-pengeluaran' }}">
                        {{ $selisih <= 0 ? 'Lebih hemat' : 'Lebih tinggi' }}
                    </span>
                @endif
            </div>
        </div>
    </div>

    <!-- TARGET HARIAN & TABUNGAN -->
    <div class="card fade-up-delay-2">
        <h3 class="section-title"><span class="icon">🎯</span> Target & Tabungan</h3>

        <div class="row-item mb-14" style="border-left:4px solid {{ $targetHarian['is_set'] ? $targetHarian['warna'] : 'var(--mv-line)' }};">
            <div>
                <span class="row-label">Target harian</span>
                @if($targetHarian['is_set'])
                    <span class="status-badge" style="background:{{ $targetHarian['warna'] }};">
                        {{ strtoupper($targetHarian['label']) }}
                    </span>
                    <p class="insight-text mt-8">
                        Batas belanja Rp {{ number_format($targetHarian['batas_harian'], 0, ',', '.') }} ·
                        pengeluaran hari ini Rp {{ number_format($targetHarian['pengeluaran_today'], 0, ',', '.') }}
                    </p>
                    <p class="insight-text text-muted">{{ $targetHarian['pesan'] }}</p>
                @else
                    <p class="insight-text text-muted mt-8">
                        Belum ada target harian.
                        <a href="{{ route('setup-anggaran.index') }}">Atur anggaran</a> supaya Monevia bisa menghitung batas belanja harian.
                    </p>
                @endif
            </div>
        </div>

        @if($targetTabungan->count() > 0)
            @php
                $topT = $targetTabungan->first();
                $prog = $topT->jumlah_target > 0 ? min(100, round(($topT->tabungan_saat_ini / $topT->jumlah_target) * 100)) : 0;
            @endphp
            <div class="row-item is-highlight">
                <div class="flex-1">
                    <span class="row-label">Target tabungan</span>
                    <span class="row-value">{{ $topT->nama_target }}</span>
                    <div class="progress-bar-bg progress-bar-sm">
                        <div class="progress-bar-fill is-green" style="width:{{ $prog }}%;"></div>
                    </div>
                    <small class="help">
                        Terkumpul Rp {{ number_format($topT->tabungan_saat_ini, 0, ',', '.') }}
                        dari Rp {{ number_format($topT->jumlah_target, 0, ',', '.') }}
                    </small>
                </div>
                <div class="row-tail text-green" style="font-size:16px;">{{ $prog }}%</div>
            </div>
        @else
            <div class="row-item">
                <div>
                    <span class="row-label">Target tabungan</span>
                    <span class="row-value">Belum ada</span>
                    <small class="help">
                        <a href="{{ url('/target-tabungan/tambah') }}">Buat target tabungan</a> untuk memantau progres menabungmu.
                    </small>
                </div>
            </div>
        @endif
    </div>
</div>

<div class="form-actions">
    <a href="{{ url('/laporan') }}" class="btn btn-secondary">Lihat laporan lengkap</a>
    <a href="{{ url('/dashboard') }}" class="btn btn-secondary">Kembali ke dashboard</a>
</div>
@endsection
