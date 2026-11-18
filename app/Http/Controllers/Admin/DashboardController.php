<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        // Doanh thu 7 ngày gần nhất (tính mọi đơn không bị huỷ)
        $from = now()->subDays(6)->startOfDay();
        $byDay = Order::where('status', '!=', 'cancelled')
            ->where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) AS day, SUM(total_price) AS total, COUNT(*) AS orders')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $chart = ['labels' => [], 'revenue' => [], 'orders' => []];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i);
            $row = $byDay->get($day->toDateString());
            $chart['labels'][] = $day->format('d/m');
            $chart['revenue'][] = (int) ($row->total ?? 0);
            $chart['orders'][] = (int) ($row->orders ?? 0);
        }

        // Top 5 món bán chạy (đơn không bị huỷ)
        $topProducts = OrderItem::join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', '!=', 'cancelled')
            ->selectRaw('product_name, SUM(quantity) AS sold, SUM(quantity * price) AS revenue')
            ->groupBy('product_name')
            ->orderByDesc('sold')
            ->take(5)
            ->get();

        return view('admin.dashboard', [
            'totalUsers' => User::where('role', 'user')->count(),
            'totalProducts' => Product::count(),
            'totalOrders' => Order::count(),
            'pendingOrders' => Order::where('status', 'pending')->count(),
            'revenue' => Order::where('status', 'delivered')->sum('total_price'),
            'recentOrders' => Order::latest()->take(6)->get(),
            'lowStock' => Product::where('stock', '<=', 5)->orderBy('stock')->take(6)->get(),
            'chart' => $chart,
            'topProducts' => $topProducts,
        ]);
    }
}
