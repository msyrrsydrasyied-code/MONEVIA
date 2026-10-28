<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Monevia — Personal Money Coach')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/monevia.css') }}">
    @yield('head')
</head>
<body>
<div class="overlay" onclick="document.body.classList.remove('nav-open')"></div>
<div class="app-shell">
    <aside class="sidebar">
        <a href="{{ url('/dashboard') }}" class="sidebar-brand">
            <img src="{{ asset('images/logo.svg') }}" alt="Monevia">
            <div>
                <strong>Monevia</strong>
                <small>Personal Money Coach</small>
            </div>
        </a>
        <div class="nav-section">Utama</div>
        <a href="{{ url('/dashboard') }}" class="nav-link {{ Request::is('dashboard') ? 'active' : '' }}"><span class="dot"></span> Dashboard</a>
        <a href="{{ url('/insight') }}" class="nav-link {{ Request::is('insight*') ? 'active' : '' }}"><span class="dot"></span> Insight</a>
        <a href="{{ url('/transaksi') }}" class="nav-link {{ Request::is('transaksi*') ? 'active' : '' }}"><span class="dot"></span> Transaksi</a>
        <a href="{{ url('/target-tabungan') }}" class="nav-link {{ Request::is('target-tabungan*') ? 'active' : '' }}"><span class="dot"></span> Target Tabungan</a>
        <div class="nav-section">Coach</div>
        <a href="{{ route('setup-anggaran.index') }}" class="nav-link {{ Request::is('setup-anggaran*') || Request::is('keuangan') ? 'active' : '' }}"><span class="dot"></span> Atur Anggaran</a>
        <a href="{{ url('/rekomendasi') }}" class="nav-link {{ Request::is('rekomendasi') ? 'active' : '' }}"><span class="dot"></span> Rekomendasi</a>
        <a href="{{ url('/target-harian') }}" class="nav-link {{ Request::is('target-harian*') ? 'active' : '' }}"><span class="dot"></span> Target Harian</a>
        <a href="{{ url('/laporan') }}" class="nav-link {{ Request::is('laporan*') ? 'active' : '' }}"><span class="dot"></span> Laporan</a>
        @if(Auth::user()->isAdmin())
            <div class="nav-section">Admin</div>
            <a href="{{ url('/admin') }}" class="nav-link"><span class="dot"></span> Panel Admin</a>
        @endif
        <div class="sidebar-foot">
            <div class="user-chip">
                <div class="avatar">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</div>
                <div>
                    <p>{{ Auth::user()->name }}</p>
                    <span>{{ Auth::user()->isAdmin() ? 'Administrator' : 'Pengguna' }}</span>
                </div>
            </div>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-ghost">Keluar</button>
            </form>
        </div>
    </aside>

    <div class="main">
        <div class="topbar">
            <button class="menu-btn" type="button" onclick="document.body.classList.toggle('nav-open')">☰</button>
            <div></div>
            <div class="topbar-actions">
                <a href="{{ url('/notifications') }}" class="icon-btn" title="Notifikasi">
                    🔔
                    @if(($unreadNotifCount ?? 0) > 0)
                        <span class="badge-count">{{ $unreadNotifCount }}</span>
                    @endif
                </a>
                <a href="{{ url('/transaksi/tambah') }}" class="btn btn-primary">+ Transaksi</a>
            </div>
        </div>
        <div class="container">
            @if(session('sukses'))
                <div class="alert-sukses">{{ session('sukses') }}</div>
            @endif
            @yield('content')
        </div>
        <footer class="app-footer">© {{ date('Y') }} Monevia — Money coach untuk pelajar, santri, dan mahasiswa.</footer>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
