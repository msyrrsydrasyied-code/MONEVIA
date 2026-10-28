@extends('layouts.admin')

@section('title', 'Admin — Pengguna')

@section('content')
<div class="page-head fade-up">
    <div class="page-head-row">
        <div>
            <h2>Pengguna</h2>
            <p>Buat akun klien, ubah peran, atau hapus pengguna.</p>
        </div>
    </div>
</div>

<div class="admin-split">
    <!-- FORM TAMBAH PENGGUNA -->
    <div class="card fade-up-delay-1">
        <h3 class="section-title"><span class="icon">➕</span> Tambah pengguna</h3>
        <form method="POST" action="{{ url('/admin/pengguna') }}">
            @csrf
            <div class="form-group">
                <label for="name">Nama</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required>
                @error('name')<span class="text-danger">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required>
                @error('email')<span class="text-danger">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="password">Kata sandi</label>
                <input id="password" type="password" name="password" required>
                @error('password')<span class="text-danger">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="role">Peran</label>
                <select id="role" name="role">
                    <option value="user" {{ old('role') === 'admin' ? '' : 'selected' }}>Klien / pengguna</option>
                    <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                </select>
            </div>
            <button class="btn btn-primary btn-block" type="submit">Simpan pengguna</button>
        </form>
    </div>

    <!-- TABEL PENGGUNA -->
    <div class="card table-card fade-up-delay-2">
        <div class="table-card-head">
            <h3>Daftar pengguna</h3>
            <form method="GET" action="{{ url('/admin/pengguna') }}" class="admin-search">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama atau email" aria-label="Cari pengguna">
                <button class="btn btn-secondary" type="submit">Cari</button>
                @if(request('q'))
                    <a href="{{ url('/admin/pengguna') }}" class="btn btn-secondary">Reset</a>
                @endif
            </form>
        </div>

        @if($users->isEmpty())
            <div style="padding: 22px;">
                <div class="empty-state is-compact">
                    <div class="empty-state-icon">👥</div>
                    <h4 class="empty-state-title">Pengguna tidak ditemukan</h4>
                    <p class="empty-state-text">
                        @if(request('q'))
                            Coba kata kunci lain atau reset pencarian.
                        @else
                            Belum ada pengguna. Tambahkan akun pertama lewat form di samping.
                        @endif
                    </p>
                    @if(request('q'))
                        <a href="{{ url('/admin/pengguna') }}" class="btn btn-secondary">Reset pencarian</a>
                    @endif
                </div>
            </div>
        @else
            <div class="table-card-body">
                <table class="data">
                    <thead>
                        <tr>
                            <th>Pengguna</th>
                            <th>Peran</th>
                            <th class="num-cell">Transaksi</th>
                            <th class="aksi-col">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($users as $u)
                        <tr>
                            <td>
                                <div class="user-cell">
                                    <div class="avatar">{{ strtoupper(substr($u->name, 0, 1)) }}</div>
                                    <div>
                                        <p>{{ $u->name }}</p>
                                        <span>{{ $u->email }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="role-cell">
                                @if($u->id === auth()->id())
                                    <span class="admin-badge-soft is-admin">
                                        {{ $u->role === 'admin' ? 'Admin' : 'Klien' }} (kamu)
                                    </span>
                                @else
                                    <form method="POST" action="{{ url('/admin/pengguna/'.$u->id.'/peran') }}">
                                        @csrf
                                        <select name="role" onchange="this.form.submit()" aria-label="Peran {{ $u->name }}">
                                            <option value="user" {{ $u->role === 'user' ? 'selected' : '' }}>Klien</option>
                                            <option value="admin" {{ $u->role === 'admin' ? 'selected' : '' }}>Admin</option>
                                        </select>
                                        <noscript><button type="submit" class="btn btn-edit mt-0">Simpan</button></noscript>
                                    </form>
                                @endif
                            </td>
                            <td class="num-cell">{{ $u->transaksi_count }}</td>
                            <td class="aksi-col">
                                @if($u->id !== auth()->id())
                                    <form method="POST" action="{{ url('/admin/pengguna/'.$u->id) }}"
                                          onsubmit="return confirm('Hapus pengguna {{ $u->name }} beserta seluruh datanya? Tindakan ini tidak bisa dibatalkan.')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-hapus" type="submit">Hapus</button>
                                    </form>
                                @else
                                    <span class="help">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="table-foot">
                <span>Menampilkan {{ $users->firstItem() }}–{{ $users->lastItem() }} dari {{ $users->total() }} pengguna</span>
                {{ $users->links('pagination::monevia') }}
            </div>
        @endif
    </div>
</div>
@endsection
