<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    protected $fillable = ['user_id', 'sender_id', 'is_staff', 'body', 'read_at'];

    protected function casts(): array
    {
        return ['is_staff' => 'boolean', 'read_at' => 'datetime'];
    }

    /** Khách sở hữu cuộc trò chuyện */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /** Dữ liệu gửi cho JS; $forStaff = người xem là nhân viên → tin của nhân viên hiện bên phải */
    public function toChatArray(bool $forStaff): array
    {
        return [
            'id' => $this->id,
            'body' => $this->body,
            'mine' => $this->is_staff === $forStaff,
            'sender' => $this->is_staff ? 'CSKH Bếp Nhà' : $this->user->name,
            'time' => $this->created_at->format('H:i d/m'),
        ];
    }
}
