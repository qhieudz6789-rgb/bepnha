<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable; // cho phép $user->notify(...) và $user->unreadNotifications

    // 'role' cố ý KHÔNG nằm trong $fillable để khách không tự nâng quyền admin khi đăng ký
    protected $fillable = ['name', 'email', 'phone', 'address', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed']; // tự băm bcrypt khi gán
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /** Tin nhắn trong cuộc trò chuyện của khách này với CSKH */
    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    /** Số tin CSKH gửi mà khách chưa đọc */
    public function unreadChatCount(): int
    {
        return $this->chatMessages()->where('is_staff', true)->whereNull('read_at')->count();
    }

    /** Tổng số phần trong giỏ (hiện trên icon giỏ hàng) */
    public function cartCount(): int
    {
        return (int) $this->cartItems()->sum('quantity');
    }

    /** Tổng tiền giỏ hàng */
    public function cartTotal(): int
    {
        return $this->cartItems()->with('product')->get()->sum(fn (CartItem $item) => $item->subtotal());
    }
}
