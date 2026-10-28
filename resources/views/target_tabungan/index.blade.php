@extends('layouts.app')

@section('title', 'Target Tabungan — Monevia')

@section('content')
<div class="page-head fade-up">
    <div class="page-head-row">
        <div>
            <h2>Target tabungan</h2>
            <p>Pantau progres menuju hal yang kamu inginkan.</p>
        </div>
        <div class="page-head-actions">
            <a href="{{ url('/target-tabungan/tambah') }}" class="btn btn-primary">+ Target baru</a>
        </div>
    </div>
</div>

@if(session('error'))
    <div class="alert-danger">{{ session('error') }}</div>
@endif

@if($target->isEmpty())
    <div class="card fade-up-delay-1">
        <div class="empty-state">
            <div class="empty-state-icon">🎯</div>
            <h3 class="empty-state-title">Belum ada target tabungan</h3>
            <p class="empty-state-text">
                Buat target pertama kamu, lalu isi saldo secara berkala supaya progresnya kelihatan.
            </p>
            <a href="{{ url('/target-tabungan/tambah') }}" class="btn btn-primary">+ Buat target pertama</a>
        </div>
    </div>
@else
    @foreach($target as $t)
        @php
            $progress = $t->jumlah_target > 0 ? min(100, round(($t->tabungan_saat_ini / $t->jumlah_target) * 100)) : 0;
            $sisa = $t->jumlah_target - $t->tabungan_saat_ini;
            $isSelesai = $progress >= 100;
            $hariSisa = $t->target_tanggal ? max(0, (int) \Carbon\Carbon::parse($t->target_tanggal)->startOfDay()->diffInDays(now()->startOfDay())) : null;
        @endphp
        <div class="card goal-card {{ $isSelesai ? 'is-selesai' : '' }} fade-up-delay-1">
            <div class="goal-head">
                <div>
                    <h3 class="goal-title">{{ $t->nama_target }}</h3>
                    @if($t->target_tanggal)
                        <span class="help">Target tercapai {{ date('d M Y', strtotime($t->target_tanggal)) }}</span>
                    @else
                        <span class="help">Tanpa batas waktu</span>
                    @endif
                </div>
                <div class="goal-amount">
                    <strong>Rp {{ number_format($t->tabungan_saat_ini, 0, ',', '.') }}</strong>
                    <small>dari Rp {{ number_format($t->jumlah_target, 0, ',', '.') }}</small>
                </div>
            </div>

            <div class="progress-caption">
                <span>Progres terkumpul</span>
                <span class="{{ $isSelesai ? 'text-green' : 'text-blue' }}">{{ $progress }}%</span>
            </div>
            <div class="progress-bar-bg progress-bar-lg">
                <div class="progress-bar-fill {{ $isSelesai ? 'is-green' : 'is-blue' }}" style="width: {{ $progress }}%;"></div>
            </div>

            <div class="goal-meta">
                <span>
                    @if($isSelesai)
                        <span class="badge badge-pemasukan">Selesai</span>
                    @else
                        Sisa <strong>Rp {{ number_format(max(0, $sisa), 0, ',', '.') }}</strong>
                    @endif
                </span>
                @if($hariSisa !== null && !$isSelesai)
                    <span><strong>{{ $hariSisa }}</strong> hari lagi</span>
                @endif
            </div>

            <div class="goal-actions">
                <form action="{{ url('/target-tabungan/update-saldo/' . $t->id) }}" method="POST">
                    @csrf
                    <input type="number" name="tambahan_saldo" placeholder="Jumlah ditabung" step="1000" min="1" required
                           aria-label="Tambahan saldo untuk {{ $t->nama_target }}">
                    <button type="submit" class="btn btn-primary">Tambah saldo</button>
                </form>
                <form action="{{ url('/target-tabungan/hapus/' . $t->id) }}" method="POST"
                      onsubmit="return confirm('Yakin ingin menghapus target &quot;{{ $t->nama_target }}&quot;? Tindakan ini tidak bisa dibatalkan.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-hapus">Hapus target</button>
                </form>
            </div>
        </div>
    @endforeach
@endif

<div class="form-actions">
    <a href="{{ url('/dashboard') }}" class="btn btn-secondary">Kembali ke dashboard</a>
    <a href="{{ url('/rekomendasi') }}" class="btn btn-secondary">Lihat rekomendasi</a>
</div>
@endsection
