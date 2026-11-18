<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('products')->orderBy('id')->get();

        return view('admin.categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        Category::create($request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:categories,name'],
            'icon' => ['nullable', 'string', 'max:16'],
            'description' => ['nullable', 'string', 'max:255'],
        ]));

        return back()->with('success', 'Đã thêm danh mục.');
    }

    public function edit(Category $category)
    {
        return view('admin.categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $category->update($request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('categories', 'name')->ignore($category)],
            'icon' => ['nullable', 'string', 'max:16'],
            'description' => ['nullable', 'string', 'max:255'],
        ]));

        return redirect()->route('admin.categories.index')->with('success', 'Đã cập nhật danh mục.');
    }

    public function destroy(Category $category)
    {
        if ($category->products()->exists()) {
            return back()->with('error', 'Danh mục còn món ăn, hãy chuyển hoặc xoá các món trước.');
        }
        $category->delete();

        return back()->with('success', 'Đã xoá danh mục.');
    }
}
