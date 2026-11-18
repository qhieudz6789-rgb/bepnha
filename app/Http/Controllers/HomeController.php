<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;

class HomeController extends Controller
{
    public function index()
    {
        return view('shop.home', [
            'categories' => Category::withCount('products')->orderBy('id')->get(),
            'featured' => Product::withRating()->where('is_featured', true)->latest()->take(8)->get(),
            'latest' => Product::withRating()->latest()->take(4)->get(),
            'stats' => [
                'products' => Product::count(),
                'customers' => User::where('role', 'user')->count(),
                'delivered' => Order::where('status', 'delivered')->count(),
            ],
        ]);
    }
}
