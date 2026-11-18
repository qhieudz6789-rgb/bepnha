<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\MomoPayment;
use App\Services\VietQr;
use Illuminate\Http\Request;
use RuntimeException;

class PaymentController extends Controller
{
    /** Trang thanh toán: QR chuyển khoản + nút MoMo */
    public function show(Request $request, Order $order)
    {
        $this->authorizeOwner($request, $order);

        if (! $order->canPayOnline()) {
            return redirect()->route('orders.show', $order);
        }

        return view('payment.show', [
            'order' => $order,
            'qrUrl' => VietQr::isConfigured() ? VietQr::imageUrl($order) : null,
            'bank' => config('services.vietqr'),
        ]);
    }

    /** Khách bấm "Tôi đã chuyển khoản" → chờ quán đối chiếu sao kê rồi xác nhận */
    public function confirmTransfer(Request $request, Order $order)
    {
        $this->authorizeOwner($request, $order);
        abort_unless($order->canPayOnline(), 422, 'Đơn này không cần thanh toán nữa.');

        $order->update(['payment_method' => 'vietqr', 'payment_status' => 'pending']);

        return redirect()->route('orders.show', $order)
            ->with('success', 'Cảm ơn bạn! Quán sẽ kiểm tra và xác nhận thanh toán trong ít phút.');
    }

    /** Tạo giao dịch MoMo rồi chuyển khách sang trang thanh toán của MoMo */
    public function momo(Request $request, Order $order, MomoPayment $momo)
    {
        $this->authorizeOwner($request, $order);
        abort_unless($order->canPayOnline(), 422, 'Đơn này không cần thanh toán nữa.');

        try {
            $payUrl = $momo->createPaymentUrl($order);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage().' Bạn có thể dùng chuyển khoản QR thay thế.');
        }

        $order->update(['payment_method' => 'momo']);

        return redirect()->away($payUrl);
    }

    /** MoMo chuyển trình duyệt của khách về đây sau khi thanh toán */
    public function momoReturn(Request $request, MomoPayment $momo)
    {
        $order = Order::where('payment_ref', $request->input('orderId'))->first();

        if (! $order || ! $momo->verify($request->all())) {
            return redirect()->route('orders.index')->with('error', 'Kết quả thanh toán không hợp lệ.');
        }

        if ((int) $request->input('resultCode') === 0 && (int) $request->input('amount') === $order->total_price) {
            $order->markPaid();

            return redirect()->route('orders.show', $order)->with('success', 'Thanh toán MoMo thành công!');
        }

        return redirect()->route('payment.show', $order)
            ->with('error', 'Thanh toán chưa thành công: '.$request->input('message', 'đã huỷ').' Bạn có thể thử lại.');
    }

    /**
     * MoMo gọi ngầm về server (IPN) để báo kết quả — chỉ hoạt động khi web có tên miền công khai,
     * chạy localhost thì MoMo không gọi tới được, lúc đó dựa vào momoReturn ở trên.
     */
    public function momoIpn(Request $request, MomoPayment $momo)
    {
        $order = Order::where('payment_ref', $request->input('orderId'))->first();

        if ($order && $momo->verify($request->all())
            && (int) $request->input('resultCode') === 0
            && (int) $request->input('amount') === $order->total_price) {
            $order->markPaid();
        }

        return response()->noContent();
    }

    private function authorizeOwner(Request $request, Order $order): void
    {
        abort_unless((int) $order->user_id === $request->user()->id, 403);
    }
}
