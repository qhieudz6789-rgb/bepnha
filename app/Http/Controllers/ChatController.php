<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use Illuminate\Http\Request;

/** Khung chat của khách với nhân viên CSKH (JS gọi định kỳ để lấy tin mới) */
class ChatController extends Controller
{
    /** Lấy tin nhắn có id > after; đồng thời đánh dấu tin của CSKH là khách đã đọc */
    public function messages(Request $request)
    {
        $user = $request->user();

        $messages = $user->chatMessages()->with('user')
            ->where('id', '>', $request->integer('after'))
            ->orderBy('id')
            ->take(100)
            ->get();

        $user->chatMessages()->where('is_staff', true)->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json([
            'messages' => $messages->map(fn (ChatMessage $m) => $m->toChatArray(false)),
        ]);
    }

    public function send(Request $request)
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:1000']]);
        $user = $request->user();

        $message = $user->chatMessages()->create([
            'sender_id' => $user->id,
            'is_staff' => false,
            'body' => trim($data['body']),
        ]);

        return response()->json(['message' => $message->load('user')->toChatArray(false)]);
    }
}
