<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;

/**
 * Giỏ hàng. Mọi thao tác đều chạy được 2 cách:
 *  - gọi AJAX (fetch) → trả JSON, trang không tải lại
 *  - submit form thường (khi tắt JavaScript) → redirect về trang trước
 */
class CartController extends Controller
{
    public function index(Request $request)
    {
        $items = $request->user()->cartItems()->with('product')->get();

        return view('cart.index', [
            'items' => $items,
            'total' => $items->sum(fn (CartItem $item) => $item->subtotal()),
            'user' => $request->user(),
        ]);
    }

    /** Thêm vào giỏ — kiểm tra tồn kho (lớp kiểm tra 1) */
    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);
        $product = Product::findOrFail($data['product_id']);
        $quantity = $data['quantity'] ?? 1;

        if ($product->isOutOfStock()) {
            return $this->reply($request, "Món \"{$product->name}\" đã hết hàng.", false);
        }

        $item = $request->user()->cartItems()->firstOrNew(['product_id' => $product->id]);
        $newQuantity = ($item->quantity ?? 0) + $quantity;

        if ($newQuantity > $product->stock) {
            return $this->reply($request, "Chỉ còn {$product->stock} phần \"{$product->name}\" trong kho.", false);
        }

        $item->quantity = $newQuantity;
        $item->save();

        return $this->reply($request, "Đã thêm \"{$product->name}\" vào giỏ hàng.");
    }

    /** Đổi số lượng — không vượt quá tồn kho (lớp kiểm tra 2) */
    public function update(Request $request, CartItem $cartItem)
    {
        abort_unless((int) $cartItem->user_id === $request->user()->id, 403);

        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1']]);

        if ($data['quantity'] > $cartItem->product->stock) {
            return $this->reply($request, "Chỉ còn {$cartItem->product->stock} phần \"{$cartItem->product->name}\" trong kho.", false);
        }

        $cartItem->update($data);

        return $this->reply($request, 'Đã cập nhật số lượng.', true, ['subtotal' => vnd($cartItem->subtotal())]);
    }

    public function destroy(Request $request, CartItem $cartItem)
    {
        abort_unless((int) $cartItem->user_id === $request->user()->id, 403);
        $cartItem->delete();

        return $this->reply($request, 'Đã xoá món khỏi giỏ hàng.');
    }

    /** Trả JSON cho AJAX (kèm số lượng + tổng tiền giỏ mới), hoặc redirect cho form thường */
    private function reply(Request $request, string $message, bool $ok = true, array $extra = [])
    {
        if ($request->expectsJson()) {
            $user = $request->user();

            return response()->json($extra + [
                'message' => $message,
                'count' => $user->cartCount(),
                'total' => vnd($user->cartTotal()),
            ], $ok ? 200 : 422);
        }

        return back()->with($ok ? 'success' : 'error', $message);
    }
}
