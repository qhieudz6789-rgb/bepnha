<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Notifications\Notification;

/**
 * Thông báo gửi cho khách mỗi khi đơn đổi trạng thái hoặc thanh toán thành công.
 * Lưu vào bảng `notifications` (kênh database) → hiện ở chuông thông báo trên web.
 */
class OrderStatusChanged extends Notification
{
    private const TEXT = [
        'pending' => ['📝', 'Đặt hàng thành công', 'Đơn #:id đã được ghi nhận, quán sẽ xác nhận trong ít phút.'],
        'confirmed' => ['✅', 'Quán đã nhận đơn', 'Bếp Nhà đã nhận đơn #:id của bạn.'],
        'preparing' => ['👩‍🍳', 'Bếp đang nấu', 'Đơn #:id đã được chuyển xuống bếp, món đang được chuẩn bị.'],
        'delivering' => ['🛵', 'Shipper đang giao', 'Đơn #:id đang trên đường đến bạn.'],
        'delivered' => ['🏁', 'Giao hàng thành công', 'Đơn #:id đã hoàn tất. Chúc bạn ngon miệng, nhớ đánh giá món nhé!'],
        'cancelled' => ['❌', 'Đơn đã bị huỷ', 'Đơn #:id đã bị huỷ.'],
        'paid' => ['💳', 'Thanh toán thành công', 'Đã nhận :amount cho đơn #:id. Cảm ơn bạn!'],
    ];

    public function __construct(private Order $order, private string $event)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        [$icon, $title, $message] = self::TEXT[$this->event];
        $message = strtr($message, [':id' => $this->order->id, ':amount' => vnd($this->order->total_price)]);

        if ($this->event === 'delivering' && $this->order->shipper_name) {
            $message .= " Shipper: {$this->order->shipper_name}".($this->order->shipper_phone ? " – {$this->order->shipper_phone}" : '').'.';
        }
        if ($this->event === 'cancelled' && $this->order->payment_status === 'refund') {
            $message .= ' Quán sẽ liên hệ hoàn tiền cho bạn.';
        }

        return [
            'order_id' => $this->order->id,
            'event' => $this->event,
            'icon' => $icon,
            'title' => $title,
            'message' => $message,
        ];
    }
}
