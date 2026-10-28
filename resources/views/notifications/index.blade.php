@extends('layouts.app')

@section('title', 'Notifikasi — Monevia')

@section('content')
<div class="page-head fade-up">
    <div class="page-head-row">
        <div>
            <h2>Notifikasi</h2>
            <p>Pengingat dari Monevia tentang kondisi uangmu.</p>
        </div>
        @if($unreadCount > 0)
            <div class="page-head-actions">
                <form action="{{ route('notifications.read-all') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-secondary">Tandai semua dibaca</button>
                </form>
            </div>
        @endif
    </div>
</div>

@if($unreadCount > 0)
    <div class="alert-warning fade-up">
        Kamu punya <strong>{{ $unreadCount }} notifikasi</strong> yang belum dibaca.
    </div>
@endif

<div class="card fade-up-delay-1">
    @if($notifications->isEmpty())
        <div class="empty-state">
            <div class="empty-state-icon">🔔</div>
            <h3 class="empty-state-title">Belum ada notifikasi</h3>
            <p class="empty-state-text">
                Monevia akan memberi tahu kalau pengeluaranmu mendekati batas harian atau sisa uang menipis.
            </p>
            <a href="{{ url('/transaksi') }}" class="btn btn-primary">Lihat transaksi</a>
        </div>
    @else
        <div class="notif-list">
            @foreach($notifications as $notif)
                @php
                    $ikon = match ($notif->tipe) {
                        'danger' => '🚨',
                        'warning' => '⚠️',
                        'success' => '✅',
                        default => '💰',
                    };
                @endphp
                <div class="notif-item is-{{ $notif->tipe }} {{ $notif->dibaca ? '' : 'is-unread' }}">
                    <div class="notif-icon">{{ $ikon }}</div>

                    <div class="notif-body">
                        <div class="notif-head">
                            <span class="notif-title">{{ $notif->judul }}</span>
                            @if(!$notif->dibaca)
                                <span class="badge badge-baru">Baru</span>
                            @endif
                            <span class="badge badge-user">{{ ucfirst($notif->tipe) }}</span>
                        </div>
                        <p class="notif-text">{{ $notif->pesan }}</p>
                        <span class="notif-time">
                            {{ $notif->created_at->diffForHumans() }} · {{ $notif->created_at->format('d M Y, H:i') }}
                        </span>
                    </div>

                    <div class="notif-actions">
                        @if(!$notif->dibaca)
                            <form action="{{ route('notifications.read', $notif->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-edit">Tandai dibaca</button>
                            </form>
                        @endif
                        <form action="{{ route('notifications.delete', $notif->id) }}" method="POST"
                              onsubmit="return confirm('Hapus notifikasi ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-hapus">Hapus</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="table-foot">
            <span>Menampilkan notifikasi terbaru</span>
            {{ $notifications->links('pagination::monevia') }}
        </div>
    @endif
</div>
@endsection
