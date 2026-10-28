<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        $notifications = Notification::where('user_id', $userId)
            ->latest()
            ->paginate(20);

        $unreadCount = Notification::where('user_id', $userId)
            ->where('dibaca', false)
            ->count();

        return view('notifications.index', compact('notifications', 'unreadCount'));
    }

    public function markAsRead($id)
    {
        $userId = Auth::id();
        $notification = Notification::where('user_id', $userId)->findOrFail($id);
        $notification->markAsRead();

        if (request()->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back();
    }

    public function markAllAsRead()
    {
        $userId = Auth::id();
        Notification::where('user_id', $userId)
            ->where('dibaca', false)
            ->update(['dibaca' => true]);

        if (request()->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('sukses', 'Semua notifikasi ditandai sudah dibaca');
    }

    public function getUnreadCount()
    {
        $userId = Auth::id();
        $count = Notification::where('user_id', $userId)
            ->where('dibaca', false)
            ->count();

        return response()->json(['count' => $count]);
    }

    public function getLatestNotifications($limit = 5)
    {
        $userId = Auth::id();
        $notifications = Notification::where('user_id', $userId)
            ->latest()
            ->take($limit)
            ->get();

        return response()->json($notifications);
    }

    public function destroy($id)
    {
        $userId = Auth::id();
        $notification = Notification::where('user_id', $userId)->findOrFail($id);
        $notification->delete();

        if (request()->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('sukses', 'Notifikasi dihapus');
    }
}