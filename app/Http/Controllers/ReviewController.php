<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /** Gửi (hoặc sửa) đánh giá — chỉ khách đã nhận món mới được đánh giá */
    public function store(Request $request, Product $product)
    {
        if (! $product->canBeReviewedBy($request->user())) {
            return back()->with('error', 'Bạn chỉ có thể đánh giá món đã đặt và nhận hàng thành công.');
        }

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:500'],
        ]);

        Review::updateOrCreate(
            ['user_id' => $request->user()->id, 'product_id' => $product->id],
            $data
        );

        return back()->with('success', 'Cảm ơn bạn đã đánh giá món!');
    }
}
