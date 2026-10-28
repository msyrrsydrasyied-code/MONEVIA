<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Monevia')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/monevia.css') }}">
</head>
<body>
<div class="overlay" onclick="document.body.classList.remove('nav-open')"></div>
<div class="app-shell">
    <aside class="sidebar">
        <a href="{{ url('/admin') }}" class="sidebar-brand">
            <img src="{{ asset('images/logo.svg') }}" alt="Monevia">
            <div>
                <strong>Monevia</strong>
                <small>Panel Admin</small>
            </div>
        </a>
        <div class="nav-section">Kelola</div>
        <a href="{{ url('/admin') }}" class="nav-link {{ Request::is('admin') ? 'active' : '' }}"><span class="dot"></span> Ringkasan</a>
        <a href="{{ url('/admin/pengguna') }}" class="nav-link {{ Request::is('admin/pengguna*') ? 'active' : '' }}"><span class="dot"></span> Pengguna</a>
        <a href="{{ url('/admin/kategori') }}" class="nav-link {{ Request::is('admin/kategori*') ? 'active' : '' }}"><span class="dot"></span> Kategori</a>
        <a href="{{ url('/admin/transaksi') }}" class="nav-link {{ Request::is('admin/transaksi*') ? 'active' : '' }}"><span class="dot"></span> Semua Transaksi</a>
        <div class="nav-section">Aplikasi</div>
        <a href="{{ url('/dashboard') }}" class="nav-link"><span class="dot"></span> Mode Klien</a>
        <div class="sidebar-foot">
            <div class="user-chip">
                <div class="avatar">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</div>
                <div>
                    <p>{{ Auth::user()->name }}</p>
                    <span>Administrator</span>
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
            <button class="menu-btn" type="button" onclick="document.body.classList.toggle('nav-open')" aria-label="Buka menu navigasi">☰</button>
            <span class="admin-badge-soft">Panel Admin</span>
            <div class="topbar-actions">
                <a href="{{ url('/dashboard') }}" class="btn btn-secondary">Buka aplikasi klien</a>
            </div>
        </div>

        <div class="container">
            @if(session('sukses'))
                <div class="alert-sukses">{{ session('sukses') }}</div>
            @endif
            @if(session('error'))
                <div class="alert-danger">{{ session('error') }}</div>
            @endif
            @if($errors->any())
                <div class="alert-warning">
                    <ul class="alert-list">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @yield('content')
        </div>
        <footer class="app-footer">Panel admin Monevia — © {{ date('Y') }}</footer>
    </div>
</div>
@stack('scripts')
</body>
</html>
