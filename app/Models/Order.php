<?php

namespace App\Models;

use App\Notifications\OrderStatusChanged;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    public const STATUSES = [
        'pending' => 'Chờ xác nhận',
        'confirmed' => 'Quán đã nhận đơn',
        'preparing' => 'Bếp đang nấu',
        'delivering' => 'Shipper đang giao',
        'delivered' => 'Hoàn tất',
        'cancelled' => 'Đã huỷ',
    ];

    /** Quy trình xử lý đơn theo thứ tự, và tên nút bấm để chuyển sang bước đó (trang admin) */
    public const FLOW = ['pending', 'confirmed', 'preparing', 'delivering', 'delivered'];

    public const ACTIONS = [
        'confirmed' => '✅ Nhận đơn',
        'preparing' => '👩‍🍳 Chuyển cho bếp',
        'delivering' => '🛵 Giao cho shipper',
        'delivered' => '🏁 Xác nhận đã giao xong',
    ];

    public const PAYMENT_METHODS = [
        'cod' => '💵 Tiền mặt khi nhận hàng',
        'vietqr' => '🏦 Chuyển khoản QR',
        'momo' => '🟣 Ví MoMo',
    ];

    public const PAYMENT_STATUSES = [
        'unpaid' => 'Chưa thanh toán',
        'pending' => 'Chờ quán xác nhận tiền',
        'paid' => 'Đã thanh toán',
        'refund' => 'Chờ hoàn tiền',
    ];

    protected $fillable = [
        'user_id', 'customer_name', 'phone', 'address', 'note', 'total_price', 'status',
        'payment_method', 'payment_status', 'shipper_name', 'shipper_phone',
    ];

    protected function casts(): array
    {
        return ['total_price' => 'integer', 'paid_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function paymentMethodLabel(): string
    {
        return self::PAYMENT_METHODS[$this->payment_method] ?? $this->payment_method;
    }

    public function paymentStatusLabel(): string
    {
        return self::PAYMENT_STATUSES[$this->payment_status] ?? $this->payment_status;
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /** Khách còn thanh toán online được không */
    public function canPayOnline(): bool
    {
        return in_array($this->payment_status, ['unpaid', 'pending'], true)
            && ! in_array($this->status, ['cancelled', 'delivered'], true);
    }

    /** Bước tiếp theo trong quy trình (null nếu đã xong hoặc đã huỷ) */
    public function nextStatus(): ?string
    {
        $index = array_search($this->status, self::FLOW, true);

        return $index === false ? null : (self::FLOW[$index + 1] ?? null);
    }

    /** Nội dung chuyển khoản để quán đối chiếu sao kê, vd "BEPNHA 12" */
    public function transferContent(): string
    {
        return 'BEPNHA '.$this->id;
    }

    /** Gửi thông báo cho khách (lưu vào bảng notifications) */
    public function notifyCustomer(string $event): void
    {
        $this->user?->notify(new OrderStatusChanged($this, $event));
    }

    /** Đổi trạng thái + báo cho khách */
    public function moveTo(string $status, array $extra = []): void
    {
        if ($status === 'cancelled') {
            $this->cancel();

            return;
        }
        $this->update(['status' => $status] + $extra);
        $this->notifyCustomer($status);
    }

    public function markPaid(): void
    {
        if ($this->isPaid()) {
            return;
        }
        $this->forceFill(['payment_status' => 'paid', 'paid_at' => now()])->save();
        $this->notifyCustomer('paid');
    }

    /**
     * Huỷ đơn + hoàn số lượng về kho (dùng chung cho khách và admin).
     * Khoá dòng đơn trong transaction để 2 lần huỷ cùng lúc không hoàn kho 2 lần.
     */
    public function cancel(): void
    {
        $cancelled = DB::transaction(function () {
            $order = static::whereKey($this->id)->lockForUpdate()->first();
            if ($order->status === 'cancelled') {
                return false;
            }
            foreach ($order->items as $item) {
                if ($item->product_id) {
                    Product::whereKey($item->product_id)->increment('stock', $item->quantity);
                }
            }
            $order->update([
                'status' => 'cancelled',
                // Đã trả tiền online thì đánh dấu cần hoàn tiền cho khách
                'payment_status' => $order->payment_status === 'paid' ? 'refund' : $order->payment_status,
            ]);

            return true;
        });

        $this->refresh();
        if ($cancelled) {
            $this->notifyCustomer('cancelled');
        }
    }
}
