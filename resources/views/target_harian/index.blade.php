@extends('layouts.app')

@section('title', 'Target Harian — Monevia')

@section('content')
<div class="page-head fade-up">
    <h2>Target Harian</h2>
    <p>Batas pengeluaran harian yang aman, dihitung otomatis dari sisa uang dan sisa hari.</p>
</div>

@php $kelasStatus = 'is-' . ($statusInfo['status'] ?? 'belum_atur'); @endphp

@if(!$targetInfo['is_set'])
    <!-- BELUM ADA TARGET -->
    <div class="card empty-state fade-up-delay-1">
        <div class="empty-state-icon">🎯</div>
        <h3 class="empty-state-title">Target harian belum dibuat</h3>
        <p class="empty-state-text">
            Atur uang, periode, dan pembagian anggaran dulu supaya Monevia bisa menghitung batas belanja harianmu.
        </p>
        <a href="{{ route('setup-anggaran.index') }}" class="btn btn-primary">Mulai atur anggaran</a>
    </div>
@else
    <!-- RINGKASAN BATAS BELANJA -->
    <div class="card status-card {{ $kelasStatus }} fade-up-delay-1">
        <div class="status-head">
            <div class="coach-ident">
                <div class="coach-icon">🎯</div>
                <div class="status-head-text">
                    <h3>Batas belanja hari ini</h3>
                    <p class="help">Sisa uang dibagi dengan sisa hari, jadi otomatis menyesuaikan.</p>
                </div>
            </div>
            <span class="status-badge {{ $kelasStatus }}">
                <span class="status-dot"></span>
                {{ $statusInfo['ikon'] }} {{ $statusInfo['label'] }}
            </span>
        </div>

        <div class="grid-4 mb-18">
            <div class="stat">
                <label>Batas hari ini</label>
                <h4 class="{{ $targetInfo['batas_harian'] > 0 ? 'text-green' : 'text-muted' }}">
                    Rp {{ number_format($targetInfo['batas_harian'], 0, ',', '.') }}
                </h4>
                <small>{{ $targetInfo['batas_harian'] > 0 ? 'per hari' : 'belum bisa dihitung' }}</small>
            </div>

            <div class="stat">
                <label>Pengeluaran hari ini</label>
                <h4 class="{{ $pengeluaranHariIni > $targetInfo['batas_harian'] ? 'text-red' : 'text-blue' }}">
                    Rp {{ number_format($pengeluaranHariIni, 0, ',', '.') }}
                </h4>
                <small>tercatat hari ini</small>
            </div>

            <div class="stat">
                <label>Uang tersedia</label>
                <h4 class="{{ $targetInfo['saldo_tersedia'] < 0 ? 'text-red' : '' }}">
                    {{ $targetInfo['saldo_tersedia'] < 0 ? '−' : '' }} Rp {{ number_format(abs($targetInfo['saldo_tersedia']), 0, ',', '.') }}
                </h4>
                <small>saldo aktual − uang disisihkan</small>
            </div>

            <div class="stat">
                <label>Sisa hari</label>
                <h4 class="{{ $targetInfo['sisa_hari'] > 0 ? 'text-purple' : 'text-red' }}">
                    {{ $targetInfo['sisa_hari'] }} hari
                </h4>
                <small>dari {{ $targetInfo['target_hari'] }} hari</small>
            </div>
        </div>

        @if($targetInfo['periode_selesai'])
            <div class="alert-warning mb-18">
                Periode {{ $targetInfo['target_hari'] }} hari kamu sudah selesai, jadi batas harian tidak dihitung lagi.
                <a href="{{ route('setup-anggaran.index') }}" class="link-plain">Atur periode baru</a> kalau mau lanjut.
            </div>
        @elseif($targetInfo['saldo_aktual'] < 0)
            {{-- Saldo negatif tidak disembunyikan: batas harian 0, saldo tetap negatif. --}}
            <div class="alert-danger mb-18">
                Pengeluaranmu melebihi uang yang tersedia sebesar
                <strong>Rp {{ number_format(abs($targetInfo['saldo_aktual']), 0, ',', '.') }}</strong>,
                jadi batas harian saat ini <strong>Rp 0</strong> sampai ada pemasukan baru.
                <a href="{{ route('setup-anggaran.index') }}" class="link-plain">Atur ulang anggaran</a> kalau perlu.
            </div>
        @elseif($targetInfo['saldo_tersedia'] <= 0)
            <div class="alert-danger mb-18">
                Uang yang tersedia sudah habis, jadi batas harian belum bisa dihitung.
                Kurangi dulu uang yang disisihkan, catat pemasukan baru, atau
                <a href="{{ route('setup-anggaran.index') }}" class="link-plain">atur ulang anggaran</a>.
            </div>
        @endif

        <!-- PROGRES PERIODE -->
        <div class="mb-18">
            <div class="progress-caption">
                <span>Progres periode ({{ $targetInfo['hari_terlewat'] }} dari {{ $targetInfo['target_hari'] }} hari)</span>
                <span class="text-green">{{ $targetInfo['progress_persen'] }}%</span>
            </div>
            <div class="progress-bar-bg progress-bar-lg">
                <div class="progress-bar-fill is-green" style="width: {{ $targetInfo['progress_persen'] }}%;"></div>
            </div>
            <div class="periode-meta">
                <span>Tanggal mulai: <strong>{{ $targetInfo['tanggal_mulai'] }}</strong></span>
                <span>Target selesai: <strong>{{ $targetInfo['tanggal_selesai'] }}</strong></span>
            </div>
        </div>

        <!-- PESAN MONEY COACH -->
        <div class="coach-card {{ $kelasStatus }}">
            <div class="coach-head">
                <h4 class="coach-title mb-0">💬 Monevia Money Coach</h4>
                <a href="{{ route('setup-anggaran.index') }}" class="btn btn-secondary">Atur ulang anggaran</a>
            </div>
            <p class="coach-text">{{ $statusInfo['pesan'] }}</p>
        </div>
    </div>

    <div class="form-actions">
        <a href="{{ url('/dashboard') }}" class="btn btn-secondary">Kembali ke dashboard</a>
        <a href="{{ url('/laporan') }}" class="btn btn-secondary">Lihat laporan</a>
    </div>
@endif
@endsection
