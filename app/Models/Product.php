<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'category_id', 'name', 'description', 'price', 'sale_price', 'image', 'stock', 'is_featured',
    ];

    // Luôn nạp kèm danh mục (để hiện icon danh mục khi món chưa có ảnh)
    protected $with = ['category'];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'sale_price' => 'integer',
            'stock' => 'integer',
            'is_featured' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /** Scope: kèm điểm trung bình (reviews_avg_rating) và số lượt đánh giá (reviews_count) */
    public function scopeWithRating(Builder $query): Builder
    {
        return $query->withAvg('reviews', 'rating')->withCount('reviews');
    }

    /** Giá bán thực tế: có giá khuyến mãi thì dùng giá khuyến mãi */
    public function finalPrice(): int
    {
        return $this->sale_price ?: $this->price;
    }

    public function discountPercent(): int
    {
        return $this->sale_price ? (int) round(($this->price - $this->sale_price) / $this->price * 100) : 0;
    }

    public function isOutOfStock(): bool
    {
        return $this->stock <= 0;
    }

    public function imageUrl(): ?string
    {
        if (! $this->image) {
            return null;
        }

        // Ảnh mẫu có thể là link ngoài (khi lúc cài không tải về được); ảnh admin upload nằm trong storage
        return str_starts_with($this->image, 'http') ? $this->image : asset('storage/'.$this->image);
    }

    /** Chỉ khách đã nhận món (có đơn "Đã giao" chứa món này) mới được đánh giá */
    public function canBeReviewedBy(?User $user): bool
    {
        return $user !== null && Order::where('user_id', $user->id)
            ->where('status', 'delivered')
            ->whereHas('items', fn ($q) => $q->where('product_id', $this->id))
            ->exists();
    }
}
