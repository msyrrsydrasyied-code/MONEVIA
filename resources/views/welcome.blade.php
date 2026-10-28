@extends('layouts.guest')

@section('title', 'Monevia — Money Coach untuk Pelajar & Mahasiswa')

@section('content')
<div class="landing">
    <nav class="landing-nav">
        <a class="brand" href="/">
            <img src="{{ asset('images/logo.svg') }}" alt="Monevia" width="40" height="40">
            Monevia
        </a>
        <div style="display:flex; gap:10px;">
            <a href="{{ route('login') }}" class="btn btn-secondary">Masuk</a>
            <a href="{{ route('register') }}" class="btn btn-primary">Coba gratis</a>
        </div>
    </nav>

    <section class="hero">
        <div>
            <div class="kicker">Personal Money Coach</div>
            <h1>Kelola uang saku jadi rapi, tenang, dan mudah dipahami.</h1>
            <p class="lead">Monevia membantu pelajar, santri, dan mahasiswa mencatat transaksi, mengatur target harian, menabung, dan mendapat saran keuangan yang jelas.</p>
            <div class="hero-actions">
                <a href="{{ route('register') }}" class="btn btn-primary" style="padding:14px 22px; font-size:15px;">Mulai sekarang</a>
                <a href="{{ route('login') }}" class="btn btn-secondary" style="padding:14px 22px; font-size:15px;">Saya sudah punya akun</a>
            </div>
        </div>
        <div class="hero-card">
            <p class="kicker">Contoh dashboard</p>
            <h3 style="margin:8px 0 18px;">Ringkasan hari ini</h3>
            <div class="grid-2">
                <div class="stat hero">
                    <label>Saldo tersedia</label>
                    <h4>Rp 1.240.000</h4>
                    <small>Aman untuk 18 hari</small>
                </div>
                <div class="stat">
                    <label>Batas hari ini</label>
                    <h4 style="color:var(--mv-green);">Rp 68.000</h4>
                    <small>Sudah terpakai Rp 22.000</small>
                </div>
            </div>
            <div style="margin-top:16px;">
                <div style="display:flex; justify-content:space-between; font-size:12px; font-weight:700;"><span>Kebutuhan 50%</span><span>Tabungan 30%</span><span>Hiburan 20%</span></div>
                <div class="progress-bar-bg" style="display:flex; height:12px;">
                    <div style="width:50%; background:#2b6cb0;"></div>
                    <div style="width:30%; background:#0f6b4c;"></div>
                    <div style="width:20%; background:#c9a227;"></div>
                </div>
            </div>
        </div>
    </section>

    <section class="features">
        <div class="feature">
            <div class="kicker">01</div>
            <h3>Catat transaksi cepat</h3>
            <p>Pemasukan dan pengeluaran langsung tersimpan, bisa dicari, difilter, dan diedit kapan saja.</p>
        </div>
        <div class="feature">
            <div class="kicker">02</div>
            <h3>Target harian yang jelas</h3>
            <p>Monevia menghitung jatah belanja aman per hari supaya uangmu cukup sampai akhir periode.</p>
        </div>
        <div class="feature">
            <div class="kicker">03</div>
            <h3>Coach & laporan</h3>
            <p>Dapatkan rekomendasi 50/30/20, insight kebiasaan, dan laporan yang siap dilihat klien.</p>
        </div>
    </section>
</div>
@endsection
