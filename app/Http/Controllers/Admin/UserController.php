<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        $users = User::where('role', 'user')
            ->withCount('orders')
            ->withSum(['orders as spent' => fn ($q) => $q->where('status', 'delivered')], 'total_price')
            ->latest()
            ->paginate(20);

        return view('admin.users.index', compact('users'));
    }
}
