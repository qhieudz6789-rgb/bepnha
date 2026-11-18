<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Email hoặc mật khẩu không đúng.'])->onlyInput('email');
        }

        $request->session()->regenerate(); // chống session fixation

        return Auth::user()->isAdmin()
            ? redirect()->route('admin.dashboard')
            : redirect()->intended(route('home'));
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'phone' => ['nullable', 'regex:/^0\d{9}$/'],
            'address' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'min:6', 'confirmed'],
        ], [
            'phone.regex' => 'Số điện thoại phải gồm 10 số, bắt đầu bằng 0.',
        ]);

        $user = User::create($data); // mật khẩu tự băm nhờ cast 'hashed' trong model
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home')->with('success', 'Đăng ký thành công. Chào mừng bạn đến Bếp Nhà!');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
