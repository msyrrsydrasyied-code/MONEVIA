<?php

namespace App\View\Composers;

use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotificationComposer
{
    public function compose(View $view)
    {
        $unreadCount = 0;
        
        if (Auth::check()) {
            $unreadCount = Notification::where('user_id', Auth::id())
                ->where('dibaca', false)
                ->count();
        }
        
        $view->with('unreadNotifCount', $unreadCount);
    }
}