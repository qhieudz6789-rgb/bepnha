<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    /** Danh sách đơn: lọc theo trạng thái, tìm theo mã đơn / SĐT / tên khách */
    public function index(Request $request)
    {
        $search = trim((string) $request->input('q'));

        $orders = Order::with('user')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->input('payment') === 'pending', fn ($q) => $q->where('payment_status', 'pending'))
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('phone', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%");
                if (ctype_digit(ltrim($search, '#'))) {
                    $q->orWhere('id', (int) ltrim($search, '#')); // gõ "12" hoặc "#12" để tìm đơn số 12
                }
            }))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $order->load('items', 'user');

        return view('admin.orders.show', compact('order'));
    }

    /**
     * Chuyển trạng thái đơn (Nhận đơn → Chuyển bếp → Giao shipper → Hoàn tất, hoặc Huỷ).
     * Mỗi lần chuyển, khách nhận được thông báo trên web.
     */
    public function update(Request $request, Order $order)
    {
        if (in_array($order->status, ['cancelled', 'delivered'], true)) {
            return back()->with('error', 'Đơn đã kết thúc, không đổi trạng thái được nữa.');
        }

        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Order::STATUSES))],
            'shipper_name' => ['required_if:status,delivering', 'nullable', 'string', 'max:100'],
            'shipper_phone' => ['nullable', 'regex:/^0\d{9}$/'],
        ], [
            'shipper_name.required_if' => 'Vui lòng nhập tên shipper khi giao đơn.',
            'shipper_phone.regex' => 'Số điện thoại shipper phải gồm 10 số, bắt đầu bằng 0.',
        ]);

        if ($data['status'] === $order->status) {
            return back()->with('error', 'Đơn đang ở trạng thái này rồi.');
        }

        $extra = $data['status'] === 'delivering'
            ? ['shipper_name' => $data['shipper_name'], 'shipper_phone' => $data['shipper_phone'] ?? null]
            : [];
        $order->moveTo($data['status'], $extra);

        return back()->with('success', 'Đã cập nhật: '.Order::STATUSES[$data['status']].'. Khách đã nhận được thông báo.');
    }

    /** Quán đã thấy tiền chuyển khoản trong tài khoản → xác nhận thanh toán */
    public function confirmPayment(Order $order)
    {
        if ($order->isPaid() || $order->status === 'cancelled') {
            return back()->with('error', 'Đơn này không cần xác nhận thanh toán.');
        }
        $order->markPaid();

        return back()->with('success', "Đã xác nhận thanh toán đơn #{$order->id}.");
    }
}
