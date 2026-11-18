<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Tích hợp cổng thanh toán MoMo (API v2, chữ ký HMAC-SHA256).
 * Luồng: tạo yêu cầu → MoMo trả payUrl → chuyển khách sang MoMo → MoMo quay về redirectUrl kèm kết quả + chữ ký.
 */
class MomoPayment
{
    private array $config;

    public function __construct()
    {
        $this->config = config('services.momo');
    }

    /** Tạo giao dịch, trả về link trang thanh toán của MoMo */
    public function createPaymentUrl(Order $order): string
    {
        // Mỗi lần thanh toán cần mã orderId khác nhau (khách có thể bấm thanh toán lại)
        $orderId = 'BEPNHA'.$order->id.'_'.now()->format('YmdHis');
        $order->forceFill(['payment_ref' => $orderId])->save();

        $params = [
            'accessKey' => $this->config['access_key'],
            'amount' => (string) $order->total_price,
            'extraData' => '',
            'ipnUrl' => route('payment.momo.ipn'),
            'orderId' => $orderId,
            'orderInfo' => 'Thanh toan don hang #'.$order->id.' tai Bep Nha',
            'partnerCode' => $this->config['partner_code'],
            'redirectUrl' => route('payment.momo.return'),
            'requestId' => $orderId,
            'requestType' => $this->config['request_type'],
        ];

        $body = $params + [
            'partnerName' => 'Bếp Nhà Shop',
            'storeId' => 'BepNha',
            'lang' => 'vi',
            'autoCapture' => true,
            'signature' => $this->sign($params),
        ];
        unset($body['accessKey']); // accessKey chỉ dùng để ký, không gửi đi
        $body['amount'] = $order->total_price; // MoMo yêu cầu kiểu số

        try {
            $response = Http::timeout(30)->acceptJson()->post($this->config['endpoint'], $body)->json();
        } catch (\Throwable $e) {
            throw new RuntimeException('Không kết nối được MoMo: '.$e->getMessage());
        }

        if (($response['resultCode'] ?? -1) !== 0 || empty($response['payUrl'])) {
            throw new RuntimeException('MoMo từ chối giao dịch: '.($response['message'] ?? 'không rõ lỗi'));
        }

        return $response['payUrl'];
    }

    /** Kiểm tra chữ ký MoMo gửi về (trang redirect hoặc IPN) để chắc chắn dữ liệu không bị sửa */
    public function verify(array $data): bool
    {
        $fields = ['amount', 'extraData', 'message', 'orderId', 'orderInfo', 'orderType',
            'partnerCode', 'payType', 'requestId', 'responseTime', 'resultCode', 'transId'];

        $params = ['accessKey' => $this->config['access_key']];
        foreach ($fields as $field) {
            $params[$field] = (string) ($data[$field] ?? '');
        }

        return hash_equals($this->sign($params), (string) ($data['signature'] ?? ''));
    }

    /** Ký theo quy định MoMo: các trường xếp theo thứ tự a-z, nối dạng key=value&... rồi HMAC-SHA256 bằng secretKey */
    public function sign(array $params): string
    {
        ksort($params);
        $raw = implode('&', array_map(fn ($key, $value) => "{$key}={$value}", array_keys($params), $params));

        return hash_hmac('sha256', $raw, $this->config['secret_key']);
    }
}
