<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::with('category')
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->q.'%'))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.products.index', compact('products'));
    }

    public function create()
    {
        return view('admin.products.form', ['product' => new Product, 'categories' => Category::orderBy('id')->get()]);
    }

    public function store(Request $request)
    {
        Product::create($this->validated($request));

        return redirect()->route('admin.products.index')->with('success', 'Đã thêm món mới.');
    }

    public function edit(Product $product)
    {
        return view('admin.products.form', ['product' => $product, 'categories' => Category::orderBy('id')->get()]);
    }

    public function update(Request $request, Product $product)
    {
        $product->update($this->validated($request, $product));

        return redirect()->route('admin.products.index')->with('success', 'Đã cập nhật món.');
    }

    public function destroy(Product $product)
    {
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }
        $product->delete();

        return back()->with('success', 'Đã xoá món.');
    }

    /** Validate dữ liệu form + xử lý upload ảnh (dùng chung cho thêm và sửa) */
    private function validated(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'integer', 'min:1000'],
            'sale_price' => ['nullable', 'integer', 'min:0', 'lt:price'],
            'stock' => ['required', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'max:2048'], // tối đa 2MB
        ], [
            'sale_price.lt' => 'Giá khuyến mãi phải nhỏ hơn giá gốc.',
        ]);

        $data['is_featured'] = $request->boolean('is_featured');

        if ($request->hasFile('image')) {
            if ($product?->image) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $request->file('image')->store('products', 'public');
        } else {
            unset($data['image']); // không chọn ảnh mới thì giữ ảnh cũ
        }

        return $data;
    }
}
