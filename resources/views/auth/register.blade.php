@extends('layouts.guest')

@section('title', 'Daftar — Monevia')

@section('content')
<div class="guest-wrap">
    <div class="guest-hero">
        <div>
            <a href="/" class="guest-brand">
                <img src="{{ asset('images/logo.svg') }}" width="42" height="42" alt="Monevia">
                <span>Monevia<small>Personal Money Coach</small></span>
            </a>
            <h1>Mulai kelola uang saku dalam hitungan menit.</h1>
            <p>Cukup nama, email, dan kata sandi. Tanpa istilah keuangan yang membingungkan.</p>
            <ul class="guest-points">
                <li>Masukkan uangmu dan pilih masa pakainya.</li>
                <li>Bagi sendiri ke kebutuhan, tabungan, atau hiburan.</li>
                <li>Monevia menjaga agar uangmu cukup sampai periode selesai.</li>
            </ul>
        </div>
        <p class="guest-foot">Gratis untuk mencoba.</p>
    </div>

    <div class="guest-form">
        <div class="auth-card fade-up">
            <h2 class="auth-title">Buat akun baru</h2>
            <p class="help auth-sub">Satu akun untuk semua fitur Monevia.</p>

            @if($errors->any())
                <div class="alert-danger">
                    <ul class="alert-list">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('register') }}" method="POST" class="auth-form">
                @csrf
                <div class="form-group">
                    <label for="name">Nama lengkap</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" placeholder="Nama panggilan juga boleh" required>
                    @error('name')<span class="text-danger">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label for="email">Alamat email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="nama@email.com" required>
                    @error('email')<span class="text-danger">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label for="password">Kata sandi</label>
                    <input type="password" id="password" name="password" placeholder="Minimal 6 karakter" required>
                    <small class="help">Pakai kata sandi yang mudah kamu ingat.</small>
                    @error('password')<span class="text-danger">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label for="password_confirmation">Ulangi kata sandi</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" placeholder="Ketik ulang kata sandi" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Daftar &amp; masuk</button>
            </form>

            <p class="auth-alt">Sudah punya akun? <a href="{{ route('login') }}">Masuk di sini</a></p>
        </div>
    </div>
</div>
@endsection
