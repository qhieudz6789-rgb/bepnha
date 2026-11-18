<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user();

        return view('profile.edit', [
            'user' => $user,
            'orderCount' => $user->orders()->count(),
            'spent' => $user->orders()->where('status', 'delivered')->sum('total_price'),
            'reviewCount' => $user->reviews()->count(),
        ]);
    }

    public function update(Request $request)
    {
        $request->user()->update($request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'regex:/^0\d{9}$/'],
            'address' => ['nullable', 'string', 'max:255'],
        ], [
            'phone.regex' => 'Số điện thoại phải gồm 10 số, bắt đầu bằng 0.',
        ]));

        return back()->with('success', 'Đã cập nhật thông tin cá nhân.');
    }

    public function password(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'min:6', 'confirmed', 'different:current_password'],
        ]);

        $request->user()->update(['password' => $data['password']]); // tự băm nhờ cast 'hashed'

        return back()->with('success', 'Đã đổi mật khẩu.');
    }
}
