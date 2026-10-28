@extends('layouts.app')

@section('title', 'Rekomendasi — Monevia')

@section('content')
<div class="page-head fade-up">
    <h2>Rekomendasi Money Coach</h2>
    <p>Berdasarkan anggaran yang kamu tetapkan, ini pembagian uang yang disarankan Monevia.</p>
</div>

@php
    $kelasStatus = match ($status) {
        'aman' => 'is-aman',
        'peringatan' => 'is-peringatan',
        'boros', 'saldo_habis' => 'is-boros',
        'periode_selesai' => 'is-periode-selesai',
        default => 'is-belum-atur',
    };
@endphp

<!-- CARD STATUS MONEY COACH -->
<div class="card coach-card {{ $kelasStatus }} fade-up-delay-1">
    <div class="coach-head">
        <div class="coach-ident">
            <div class="coach-icon">💡</div>
            <div>
                <h3 class="coach-title">Money Coach Monevia</h3>
                <span class="status-badge {{ $kelasStatus }}">
                    <span class="status-dot"></span>
                    {{ strtoupper($statusLabel ?? $status) }}
                </span>
            </div>
        </div>
        <a href="{{ route('setup-anggaran.index') }}" class="btn btn-secondary">Ubah Anggaran</a>
    </div>
    <p class="coach-text">{{ $pesanCoach }}</p>
</div>

<!-- RINGKASAN TOTAL UANG -->
<div class="grid-4 mb-18 fade-up-delay-2">
    <div class="stat hero">
        <label>Total uang</label>
        <h3>Rp {{ number_format($totalUang, 0, ',', '.') }}</h3>
        <small>Dibagi sesuai alokasi kamu</small>
    </div>
    <div class="metric is-red">
        <span class="metric-label">Pengeluaran wajib</span>
        <h3 class="metric-value">Rp {{ number_format($keuangan->pengeluaran_wajib, 0, ',', '.') }}</h3>
        <small class="metric-sub">{{ $keuangan->pengeluaran_wajib > 0 ? round(($keuangan->pengeluaran_wajib / $totalUang) * 100) : 0 }}% dari total</small>
    </div>
    <div class="metric is-green">
        <span class="metric-label">Target tabungan</span>
        <h3 class="metric-value">Rp {{ number_format($keuangan->target_tabungan, 0, ',', '.') }}</h3>
        <small class="metric-sub">{{ $keuangan->target_tabungan > 0 ? round(($keuangan->target_tabungan / $totalUang) * 100) : 0 }}% dari total</small>
    </div>
    <div class="metric is-purple">
        <span class="metric-label">Budget hiburan</span>
        <h3 class="metric-value">Rp {{ number_format($keuangan->budget_hiburan, 0, ',', '.') }}</h3>
        <small class="metric-sub">{{ $keuangan->budget_hiburan > 0 ? round(($keuangan->budget_hiburan / $totalUang) * 100) : 0 }}% dari total</small>
    </div>
</div>

<!-- VISUAL PEMBAGIAN REKOMENDASI -->
<div class="card fade-up-delay-2">
    <h3 class="section-title"><span class="icon">📊</span> Pembagian uang yang disarankan</h3>

    <div class="alloc-row">
        <div class="alloc-row-head">
            <div>
                <span class="alloc-row-title">🏠 Kebutuhan utama</span>
                <span class="alloc-row-note">Makan, kos, transport, listrik, internet</span>
            </div>
            <div style="text-align: right;">
                <span class="alloc-row-value text-blue">Rp {{ number_format($kebutuhan, 0, ',', '.') }}</span>
                <span class="alloc-row-note">{{ $kebutuhanPersen }}% dari total uang</span>
            </div>
        </div>
        <div class="progress-bar-bg progress-bar-lg">
            <div class="progress-bar-fill is-blue" style="width: {{ $kebutuhanPersen }}%;"></div>
        </div>
    </div>

    <div class="alloc-row">
        <div class="alloc-row-head">
            <div>
                <span class="alloc-row-title">💰 Tabungan</span>
                <span class="alloc-row-note">Dana darurat dan target jangka panjang</span>
            </div>
            <div style="text-align: right;">
                <span class="alloc-row-value text-green">Rp {{ number_format($tabungan, 0, ',', '.') }}</span>
                <span class="alloc-row-note">{{ $tabunganPersen }}% dari total uang</span>
            </div>
        </div>
        <div class="progress-bar-bg progress-bar-lg">
            <div class="progress-bar-fill is-green" style="width: {{ $tabunganPersen }}%;"></div>
        </div>
    </div>

    <div class="alloc-row">
        <div class="alloc-row-head">
            <div>
                <span class="alloc-row-title">🎮 Hiburan</span>
                <span class="alloc-row-note">Jajan, nongkrong, dan hobi</span>
            </div>
            <div style="text-align: right;">
                <span class="alloc-row-value text-purple">Rp {{ number_format($fun, 0, ',', '.') }}</span>
                <span class="alloc-row-note">{{ $funPersen }}% dari total uang</span>
            </div>
        </div>
        <div class="progress-bar-bg progress-bar-lg">
            <div class="progress-bar-fill is-purple" style="width: {{ $funPersen }}%;"></div>
        </div>
    </div>

    <div class="panel-cta {{ $totalAlokasi > $totalUang ? '' : 'is-plain' }}">
        <span>
            <strong>Total alokasi</strong><br>
            <small class="help">Seluruhnya diambil dari uang yang kamu tentukan.</small>
        </span>
        <span class="alloc-row-value {{ $totalAlokasi > $totalUang ? 'text-red' : 'text-green' }}">
            Rp {{ number_format($totalAlokasi, 0, ',', '.') }}
            @if($totalAlokasi > $totalUang)
                <span class="alloc-row-note text-red">Melebihi total uang</span>
            @endif
        </span>
    </div>
</div>

<!-- PENJELASAN ATURAN 50/30/20 -->
<div class="card note-card fade-up-delay-3">
    <h4>💡 Tentang aturan 50 / 30 / 20</h4>
    <ul>
        <li><strong>50% Kebutuhan:</strong> biaya hidup wajib seperti makan, transport, kos, dan tagihan.</li>
        <li><strong>30% Tabungan:</strong> dana darurat, investasi, dan target tabungan jangka panjang.</li>
        <li><strong>20% Hiburan:</strong> hiburan, nongkrong, belanja non-kebutuhan, dan hobi.</li>
    </ul>
    <p>Angka di halaman ini memakai pembagian yang kamu pilih sendiri di Atur Anggaran, jadi selalu sama dengan Dashboard dan Insight.</p>
</div>

<div class="form-actions">
    <a href="{{ url('/target-tabungan/tambah') }}" class="btn btn-primary">+ Buat Target Tabungan</a>
    <a href="{{ url('/transaksi/tambah') }}" class="btn btn-secondary">+ Catat Transaksi</a>
    <a href="{{ url('/dashboard') }}" class="btn btn-secondary">Kembali ke Dashboard</a>
</div>
@endsection
