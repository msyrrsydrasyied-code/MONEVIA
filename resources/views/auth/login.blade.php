@extends('layouts.guest')

@section('title', 'Masuk — Monevia')

@section('content')
<div class="guest-wrap">
    <div class="guest-hero">
        <div>
            <a href="/" class="guest-brand">
                <img src="{{ asset('images/logo.svg') }}" width="42" height="42" alt="Monevia">
                <span>Monevia<small>Personal Money Coach</small></span>
            </a>
            <h1>Masuk dan lanjutkan rencana keuanganmu.</h1>
            <p>Uang tersedia, batas belanja harian, dan catatan transaksi tersimpan rapi di satu tempat.</p>
            <ul class="guest-points">
                <li>Atur anggaran sekali, lalu Monevia hitung batas belanjamu tiap hari.</li>
                <li>Catat transaksi singkat dan langsung tahu kondisi uangmu.</li>
                <li>Saran Money Coach dengan bahasa yang mudah dipahami.</li>
            </ul>
        </div>
        <p class="guest-foot">Untuk pelajar, santri, dan mahasiswa.</p>
    </div>

    <div class="guest-form">
        <div class="auth-card fade-up">
            <h2 class="auth-title">Masuk ke Monevia</h2>
            <p class="help auth-sub">Gunakan email dan kata sandi akunmu.</p>

            @if(session('sukses'))
                <div class="alert-sukses">{{ session('sukses') }}</div>
            @endif

            @if($errors->any())
                <div class="alert-danger">
                    <ul class="alert-list">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('login') }}" method="POST" class="auth-form">
                @csrf
                <div class="form-group">
                    <label for="email">Alamat email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="nama@email.com" required>
                    @error('email')<span class="text-danger">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label for="password">Kata sandi</label>
                    <input type="password" id="password" name="password" placeholder="Masukkan kata sandi" required>
                    @error('password')<span class="text-danger">{{ $message }}</span>@enderror
                </div>
                <button type="submit" class="btn btn-primary btn-block">Masuk</button>
            </form>

            <p class="auth-alt">Belum punya akun? <a href="{{ route('register') }}">Daftar gratis</a></p>
        </div>
    </div>
</div>
@endsection
