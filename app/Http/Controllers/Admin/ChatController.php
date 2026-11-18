<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Http\Request;

/** Trang CSKH: danh sách cuộc trò chuyện + trả lời khách */
class ChatController extends Controller
{
    public function index(Request $request, ?User $user = null)
    {
        $threads = $this->threads();

        if ($request->ajax()) { // JS làm mới danh sách mỗi 5 giây
            return view('admin.chat._threads', ['threads' => $threads, 'active' => $user]);
        }

        if ($user) {
            $user->loadCount('orders');
        }

        return view('admin.chat.index', ['threads' => $threads, 'active' => $user]);
    }

    public function messages(Request $request, User $user)
    {
        $messages = $user->chatMessages()->with('user')
            ->where('id', '>', $request->integer('after'))
            ->orderBy('id')
            ->take(100)
            ->get();

        // Nhân viên đã xem → đánh dấu tin của khách là đã đọc
        $user->chatMessages()->where('is_staff', false)->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json([
            'messages' => $messages->map(fn (ChatMessage $m) => $m->toChatArray(true)),
        ]);
    }

    public function send(Request $request, User $user)
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:1000']]);

        $message = $user->chatMessages()->create([
            'sender_id' => $request->user()->id,
            'is_staff' => true,
            'body' => trim($data['body']),
        ]);

        return response()->json(['message' => $message->load('user')->toChatArray(true)]);
    }

    /** Mỗi khách đã từng chat: tin cuối + số tin chưa đọc, xếp theo tin mới nhất */
    private function threads()
    {
        return User::whereHas('chatMessages')
            ->withCount(['chatMessages as unread' => fn ($q) => $q->where('is_staff', false)->whereNull('read_at')])
            ->withMax('chatMessages as last_id', 'id')
            ->orderByDesc('last_id')
            ->get()
            ->each(fn (User $u) => $u->setRelation('lastMessage', ChatMessage::find($u->last_id)));
    }
}
