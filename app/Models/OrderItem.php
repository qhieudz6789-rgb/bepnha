<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    public $timestamps = false;

    // Lưu lại tên + giá tại thời điểm đặt, để sau này sửa/xoá món không làm sai đơn cũ
    protected $fillable = ['order_id', 'product_id', 'product_name', 'price', 'quantity'];

    protected function casts(): array
    {
        return ['price' => 'integer', 'quantity' => 'integer'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
