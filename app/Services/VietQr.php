<?php

namespace App\Services;

use App\Models\Order;

/**
 * Tạo ảnh mã QR chuyển khoản theo chuẩn VietQR (dịch vụ ảnh miễn phí img.vietqr.io).
 * Khách quét bằng app ngân hàng bất kỳ → app tự điền số tài khoản, số tiền, nội dung.
 */
class VietQr
{
    public static function isConfigured(): bool
    {
        return config('services.vietqr.account') !== '';
    }

    public static function imageUrl(Order $order): string
    {
        $c = config('services.vietqr');

        return sprintf('https://img.vietqr.io/image/%s-%s-compact2.png?%s',
            rawurlencode($c['bank']),
            rawurlencode($c['account']),
            http_build_query([
                'amount' => $order->total_price,
                'addInfo' => $order->transferContent(),
                'accountName' => $c['name'],
            ])
        );
    }
}
