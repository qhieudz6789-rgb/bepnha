<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    /** Thực đơn: lọc danh mục, tìm theo tên, sắp xếp, phân trang. Gọi bằng AJAX thì chỉ trả phần kết quả. */
    public function index(Request $request)
    {
        $query = Product::withRating()
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->q.'%'))
            ->when($request->filled('category'), fn ($q) => $q->where('category_id', $request->integer('category')));

        match ($request->input('sort')) {
            'price_asc' => $query->orderByRaw('COALESCE(sale_price, price) ASC'),
            'price_desc' => $query->orderByRaw('COALESCE(sale_price, price) DESC'),
            'rating' => $query->orderByDesc('reviews_avg_rating'),
            default => $query->latest(),
        };

        $products = $query->paginate(12)->withQueryString();

        if ($request->ajax()) {
            // no-store: để nút Back của trình duyệt không hiện lại phần HTML lẻ này thay cho cả trang
            return response()->view('shop._results', compact('products'))->header('Cache-Control', 'no-store');
        }

        return view('shop.products', [
            'products' => $products,
            'categories' => Category::orderBy('id')->get(),
        ]);
    }

    public function show(Request $request, Product $product)
    {
        $product->loadAvg('reviews', 'rating')->loadCount('reviews')
            ->load(['reviews' => fn ($q) => $q->with('user')->latest()]);

        $user = $request->user();

        return view('shop.product', [
            'product' => $product,
            'related' => Product::withRating()->where('category_id', $product->category_id)
                ->whereKeyNot($product->id)->take(4)->get(),
            'canReview' => $product->canBeReviewedBy($user),
            'myReview' => $user ? $product->reviews->firstWhere('user_id', $user->id) : null,
        ]);
    }
}
