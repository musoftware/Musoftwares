<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()->notifications()->paginate(10);

        return Inertia::render('Client/Notifications/Index', [
            'notifications' => $notifications,
        ]);
    }

    public function markRead(Request $request, $id)
    {
        $notification = $request->user()->notifications()->find($id);
        if ($notification) {
            $notification->markAsRead();

            // If the request expects JSON or doesn't want to redirect, just return back
            if ($request->wantsJson() || $request->has('no_redirect')) {
                return back();
            }

            if (isset($notification->data['url'])) {
                return redirect($notification->data['url']);
            }

            // message notifications saved before they carried a url
            if (isset($notification->data['conversation_id'])) {
                return redirect('/messages?conversation='.(int) $notification->data['conversation_id']);
            }
        }

        return back();
    }

    public function markAllRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }
}
