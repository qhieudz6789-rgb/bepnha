<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()->notifications()->paginate(15);

        return view('notifications.index', compact('notifications'));
    }

    /** Bấm vào 1 thông báo → đánh dấu đã đọc và mở đơn hàng liên quan */
    public function open(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return redirect()->route('orders.show', $notification->data['order_id']);
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Đã đánh dấu tất cả là đã đọc.');
    }

    /**
     * JS gọi định kỳ (10 giây/lần) để cập nhật chuông thông báo + số tin chat chưa đọc.
     * shortcut: dùng polling thay cho WebSocket (Laravel Reverb/Pusher) cho đơn giản, đủ dùng với lượng khách nhỏ.
     */
    public function poll(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'unread' => $user->unreadNotifications()->count(),
            'chatUnread' => $user->unreadChatCount(),
            'items' => $user->notifications()->take(6)->get()->map(fn ($n) => [
                'id' => $n->id,
                'icon' => $n->data['icon'],
                'title' => $n->data['title'],
                'message' => $n->data['message'],
                'order_id' => $n->data['order_id'],
                'url' => route('notifications.open', $n->id),
                'time' => $n->created_at->diffForHumans(),
                'read' => $n->read_at !== null,
            ]),
        ]);
    }
}
