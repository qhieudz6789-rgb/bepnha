<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = $request->user()->orders()->withCount('items')->latest()->paginate(10);

        return view('orders.index', compact('orders'));
    }

    public function show(Request $request, Order $order)
    {
        abort_unless((int) $order->user_id === $request->user()->id, 403);
        $order->load('items');

        // Lịch sử xử lý đơn = các thông báo đã gửi cho đơn này (cũ → mới)
        $history = $request->user()->notifications()
            ->where('data->order_id', $order->id)
            ->reorder()->oldest() // notifications() mặc định xếp mới → cũ, phải bỏ thứ tự đó trước
            ->get();

        return view('orders.show', compact('order', 'history'));
    }

    /** Khách tự huỷ đơn — chỉ khi đơn còn "Chờ xác nhận"; hàng được hoàn về kho */
    public function cancel(Request $request, Order $order)
    {
        abort_unless((int) $order->user_id === $request->user()->id, 403);

        if ($order->status !== 'pending') {
            return back()->with('error', 'Chỉ huỷ được đơn đang chờ xác nhận. Vui lòng chat với CSKH nếu cần hỗ trợ.');
        }
        $order->cancel();

        return back()->with('success', "Đã huỷ đơn #{$order->id}.");
    }

    /**
     * Đặt hàng từ giỏ — tất cả trong 1 transaction:
     * tạo đơn → trừ kho từng món → lưu chi tiết → xoá giỏ.
     * Lỗi ở bất kỳ bước nào thì rollback toàn bộ.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'regex:/^0\d{9}$/'],
            'address' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['required', Rule::in(array_keys(Order::PAYMENT_METHODS))],
        ], [
            'phone.regex' => 'Số điện thoại phải gồm 10 số, bắt đầu bằng 0.',
        ]);

        $user = $request->user();
        $items = $user->cartItems()->with('product')->get();

        if ($items->isEmpty()) {
            return back()->with('error', 'Giỏ hàng đang trống.');
        }

        try {
            $order = DB::transaction(function () use ($user, $items, $data) {
                $order = $user->orders()->create($data + ['status' => 'pending', 'payment_status' => 'unpaid']);
                $total = 0;

                foreach ($items as $item) {
                    $product = $item->product;

                    // Lớp kiểm tra 3: chỉ trừ kho khi còn đủ hàng. Điều kiện nằm ngay trong câu UPDATE
                    // nên 2 người đặt cùng lúc món chỉ còn 1 phần thì chỉ 1 người thành công.
                    $affected = Product::whereKey($product->id)
                        ->where('stock', '>=', $item->quantity)
                        ->decrement('stock', $item->quantity);

                    if ($affected === 0) {
                        throw new DomainException(
                            "Món \"{$product->name}\" chỉ còn {$product->fresh()->stock} phần, vui lòng giảm số lượng."
                        );
                    }

                    $price = $product->finalPrice();
                    $order->items()->create([
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'price' => $price,
                        'quantity' => $item->quantity,
                    ]);
                    $total += $price * $item->quantity;
                }

                $order->update(['total_price' => $total]);
                $user->cartItems()->delete();

                return $order;
            });
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        $order->notifyCustomer('pending');

        // Chọn thanh toán online → chuyển sang trang thanh toán; COD → về trang đơn
        return $order->payment_method === 'cod'
            ? redirect()->route('orders.show', $order)->with('success', 'Đặt hàng thành công! Bếp Nhà sẽ xác nhận đơn trong ít phút.')
            : redirect()->route('payment.show', $order)->with('success', 'Đặt hàng thành công! Vui lòng thanh toán để quán bắt đầu chuẩn bị.');
    }
}
