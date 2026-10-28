@extends('layouts.app')

@section('title', 'Dashboard — Monevia')

@section('content')
<div class="page-head fade-up">
    <div class="page-head-row">
        <div>
            <h2>Halo, {{ Auth::user()->name }} 👋</h2>
            <p>Ringkasan uangmu hari ini. Kalau sudah paham, tinggal catat pengeluarannya saja.</p>
        </div>
        <div class="page-head-actions">
            <a href="{{ url('/transaksi/tambah') }}" class="btn btn-primary">+ Catat Transaksi</a>
        </div>
    </div>
</div>

@if($errors->any())
    <div class="alert-danger fade-up">
        <strong>Ada yang perlu diperbaiki:</strong>
        <ul class="alert-list">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@php $statusClass = 'is-' . $status; @endphp

@if(!$sudahSetup)
    {{-- BELUM SETUP: tidak ada angka keuangan yang boleh tampil sebagai data user --}}
    <div class="card empty-state fade-up-delay-1">
        <div class="empty-state-icon">🧭</div>
        <h3 class="empty-state-title">Atur keuanganmu terlebih dahulu</h3>
        <p class="empty-state-text">
            Monevia belum tahu berapa uang yang kamu punya. Setelah kamu mengisi jumlah uang,
            periode, dan pembagian anggaran, semua angka di halaman ini otomatis terhitung.
        </p>
        <a href="{{ route('setup-anggaran.index') }}" class="btn btn-primary">Mulai atur keuangan</a>
    </div>

    <div class="grid-2">
        <div class="card fade-up-delay-2">
            <h3 class="section-title"><span class="icon">📊</span> Transaksi terakhir</h3>
            @if($transaksiTerakhir->isEmpty())
                <div class="empty-state is-compact">
                    <div class="empty-state-icon">🧾</div>
                    <h4 class="empty-state-title">Belum ada transaksi</h4>
                    <p class="empty-state-text">Mulai dari tombol + Transaksi.</p>
                    <a href="{{ url('/transaksi/tambah') }}" class="btn btn-primary">+ Catat transaksi</a>
                </div>
            @else
                <div class="table-card-body">
                    <table class="data">
                        <tbody>
                        @foreach($transaksiTerakhir as $t)
                            <tr>
                                <td>
                                    <strong>{{ $t->nama }}</strong>
                                    <div class="help">{{ optional($t->kategori)->nama_kategori ?? '-' }}</div>
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

        <div class="card fade-up-delay-2">
            <h3 class="section-title"><span class="icon">💡</span> Apa yang bisa dilakukan sekarang</h3>
            <div class="row-list">
                <div class="row-item">
                    <div>
                        <span class="row-label">Langkah 1</span>
                        <span class="row-value">Isi kondisi keuanganmu</span>
                        <small class="help">Jumlah uang, periode, dan pembagian 50/30/20.</small>
                    </div>
                    <a href="{{ route('setup-anggaran.index') }}" class="btn btn-secondary">Mulai</a>
                </div>
                <div class="row-item">
                    <div>
                        <span class="row-label">Langkah 2</span>
                        <span class="row-value">Catat transaksi harian</span>
                        <small class="help">Supaya batas belanja harian bisa dihitung.</small>
                    </div>
                    <a href="{{ url('/transaksi/tambah') }}" class="btn btn-secondary">Catat</a>
                </div>
                <div class="row-item">
                    <div>
                        <span class="row-label">Langkah 3</span>
                        <span class="row-value">Buat target tabungan</span>
                        <small class="help">Target adalah tujuan, belum memotong saldo.</small>
                    </div>
                    <a href="{{ url('/target-tabungan/tambah') }}" class="btn btn-secondary">Buat</a>
                </div>
            </div>
        </div>
    </div>
@else
    {{-- SUDAH SETUP: semua angka berasal dari DailyTargetService --}}
    <div class="card status-card {{ $statusClass }} fade-up-delay-1">
        <div class="status-head">
            <div class="status-head-text">
                <h3>Batas belanja hari ini</h3>
                <p class="help">Dihitung otomatis dari uang yang tersedia dan sisa hari.</p>
            </div>
            <span class="status-badge {{ $statusClass }}">
                <span class="status-dot"></span>
                {{ $statusHarian['ikon'] }} {{ $statusHarian['label'] }}
            </span>
        </div>

        <div class="grid-4 mb-18">
            <div class="stat">
                <label>Batas hari ini</label>
                <h4 class="text-green">Rp {{ number_format($targetHarian['batas_harian'], 0, ',', '.') }}</h4>
            </div>
            <div class="stat">
                <label>Sudah terpakai</label>
                <h4 class="{{ $pengeluaranHariIni > $targetHarian['batas_harian'] ? 'text-red' : 'text-blue' }}">
                    Rp {{ number_format($pengeluaranHariIni, 0, ',', '.') }}
                </h4>
            </div>
            <div class="stat">
                <label>Sisa hari</label>
                <h4>{{ $targetHarian['sisa_hari'] }} hari</h4>
            </div>
            <div class="stat">
                <label>Uang tersedia</label>
                <h4 class="{{ $saldoTersedia < 0 ? 'text-red' : '' }}">
                    {{ $saldoTersedia < 0 ? '−' : '' }} Rp {{ number_format(abs($saldoTersedia), 0, ',', '.') }}
                </h4>
            </div>
        </div>

        @if($saldoTersedia < 0)
            <div class="alert-danger mb-18">
                Pengeluaranmu melebihi uang yang tersedia sebesar
                <strong>Rp {{ number_format(abs($saldoTersedia), 0, ',', '.') }}</strong>.
                Batas harian sementara设置为 0 sampai ada pemasukan baru.
            </div>
        @endif

        <div class="panel-cta">
            <span>{{ $statusHarian['pesan'] }}</span>
            <a href="{{ route('setup-anggaran.index') }}" class="btn btn-primary">Ubah anggaran</a>
        </div>
    </div>

    {{-- MONEY COACH --}}
    <div class="card coach-card {{ $statusClass }} fade-up-delay-2">
        <div class="coach-head">
            <div class="coach-ident">
                <div class="coach-icon">💡</div>
                <div>
                    <h3 class="coach-title">Saran Money Coach</h3>
                    <span class="status-badge {{ $statusClass }}">
                        <span class="status-dot"></span>
                        {{ strtoupper($status) }}
                    </span>
                </div>
            </div>
            <a href="{{ route('setup-anggaran.index') }}" class="btn btn-secondary">Atur anggaran</a>
        </div>
        <p class="coach-text">{{ $pesanCoach }}</p>
    </div>

    {{-- RINGKASAN SALDO (satu definisi saja) --}}
    <div class="grid-4 mb-18 fade-up-delay-2">
        <div class="stat hero">
            <label>Saldo aktual</label>
            <h3>{{ $saldoAktual < 0 ? '−' : '' }} Rp {{ number_format(abs($saldoAktual), 0, ',', '.') }}</h3>
            <small>Uang awal + pemasukan − pengeluaran</small>
        </div>
        <div class="stat">
            <label>Uang disisihkan</label>
            <h3>Rp {{ number_format($uangDisisihkan, 0, ',', '.') }}</h3>
            <small>Sudah dikunci, tidak untuk belanja</small>
        </div>
        <div class="stat">
            <label>Uang tersedia</label>
            <h3 class="{{ $saldoTersedia < 0 ? 'text-red' : 'text-green' }}">
                {{ $saldoTersedia < 0 ? '−' : '' }} Rp {{ number_format(abs($saldoTersedia), 0, ',', '.') }}
            </h3>
            <small>Saldo aktual − uang disisihkan</small>
        </div>
        <div class="stat">
            <label>Total tabungan</label>
            <h3 class="text-blue">Rp {{ number_format($totalTabungan, 0, ',', '.') }}</h3>
            <small>Tercatat di target tabungan</small>
        </div>
    </div>

    <div class="grid-4 mb-18 fade-up-delay-2">
        <div class="stat">
            <label>Total pemasukan</label>
            <h3 class="text-green">Rp {{ number_format($totalPemasukan, 0, ',', '.') }}</h3>
            <small>Semua uang yang masuk</small>
        </div>
        <div class="stat">
            <label>Total pengeluaran</label>
            <h3 class="text-red">Rp {{ number_format($totalPengeluaran, 0, ',', '.') }}</h3>
            <small>Semua uang yang keluar</small>
        </div>
        <div class="stat">
            <label>Sisa periode</label>
            <h3>{{ $targetHarian['sisa_hari'] }} hari</h3>
            <small>dari {{ $targetHarian['target_hari'] }} hari target</small>
        </div>
        <div class="stat">
            <label>Periode</label>
            <h3>{{ $sudahSetup ? $targetHarian['tanggal_mulai'] : '-' }}</h3>
            <small>sampai {{ $sudahSetup ? $targetHarian['tanggal_selesai'] : '-' }}</small>
        </div>
    </div>

    <div class="grid-2">
        {{-- PEMBAGIAN ANGGARAN (nilai yang sama dengan Setup Anggaran) --}}
        <div class="card fade-up-delay-3">
            <div class="page-head-row mb-14">
                <h3 class="mt-0 mb-0">Pembagian anggaranku</h3>
                <span class="badge badge-pemasukan">Aktif</span>
            </div>

            <div class="alloc-row">
                <div class="alloc-row-head">
                    <span class="alloc-row-title">Kebutuhan ({{ $persenAlokasi['kebutuhan'] }}%)</span>
                    <span class="alloc-row-value">Rp {{ number_format($alokasi['kebutuhan'], 0, ',', '.') }}</span>
                </div>
                <div class="progress-bar-bg">
                    <div class="progress-bar-fill is-blue" style="width: {{ $persenAlokasi['kebutuhan'] }}%;"></div>
                </div>
            </div>

            <div class="alloc-row">
                <div class="alloc-row-head">
                    <span class="alloc-row-title">Tabungan ({{ $persenAlokasi['tabungan'] }}%)</span>
                    <span class="alloc-row-value">Rp {{ number_format($alokasi['tabungan'], 0, ',', '.') }}</span>
                </div>
                <div class="progress-bar-bg">
                    <div class="progress-bar-fill is-green" style="width: {{ $persenAlokasi['tabungan'] }}%;"></div>
                </div>
            </div>

            <div class="alloc-row">
                <div class="alloc-row-head">
                    <span class="alloc-row-title">Hiburan ({{ $persenAlokasi['hiburan'] }}%)</span>
                    <span class="alloc-row-value">Rp {{ number_format($alokasi['hiburan'], 0, ',', '.') }}</span>
                </div>
                <div class="progress-bar-bg">
                    <div class="progress-bar-fill is-gold" style="width: {{ $persenAlokasi['hiburan'] }}%;"></div>
                </div>
            </div>

            <div class="panel-cta is-plain">
                <span>Total dibagi <strong>Rp {{ number_format($alokasi['kebutuhan'] + $alokasi['tabungan'] + $alokasi['hiburan'], 0, ',', '.') }}</strong>
                    dari uang Rp {{ number_format($targetHarian['total_uang'], 0, ',', '.') }}.</span>
                <a href="{{ route('setup-anggaran.index') }}" class="btn btn-secondary">Ubah alokasi</a>
            </div>
        </div>

        {{-- TRANSAKSI TERAKHIR --}}
        <div class="card table-card fade-up-delay-3">
            <div class="table-card-head">
                <h3>Transaksi terakhir</h3>
                <a href="{{ url('/transaksi') }}" class="link-plain">Lihat semua</a>
            </div>

            @if($transaksiTerakhir->isEmpty())
                <div style="padding: 22px;">
                    <div class="empty-state is-compact">
                        <div class="empty-state-icon">🧾</div>
                        <h4 class="empty-state-title">Belum ada transaksi</h4>
                        <p class="empty-state-text">Mulai dari tombol + Transaksi.</p>
                        <a href="{{ url('/transaksi/tambah') }}" class="btn btn-primary">+ Catat transaksi</a>
                    </div>
                </div>
            @else
                <div class="table-card-body">
                    <table class="data">
                        <tbody>
                        @foreach($transaksiTerakhir as $t)
                            <tr>
                                <td>
                                    <strong>{{ $t->nama }}</strong>
                                    <div class="help">{{ optional($t->kategori)->nama_kategori ?? '-' }}</div>
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
@endif
@endsection