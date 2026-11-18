<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ShopController;
use Illuminate\Support\Facades\Route;

// ── Trang công khai ──
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/thuc-don', [ShopController::class, 'index'])->name('products.index');
Route::get('/mon/{product}', [ShopController::class, 'show'])->name('products.show');

// ── MoMo gọi về sau khi thanh toán (xác thực bằng chữ ký, không cần đăng nhập) ──
Route::get('/thanh-toan/momo/ket-qua', [PaymentController::class, 'momoReturn'])->name('payment.momo.return');
Route::post('/thanh-toan/momo/ipn', [PaymentController::class, 'momoIpn'])->name('payment.momo.ipn');

// ── Đăng nhập / đăng ký (chỉ khi chưa đăng nhập) ──
Route::middleware('guest')->group(function () {
    Route::get('/dang-nhap', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/dang-nhap', [AuthController::class, 'login']);
    Route::get('/dang-ky', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/dang-ky', [AuthController::class, 'register']);
});

// ── Khách đã đăng nhập ──
Route::middleware('auth')->group(function () {
    Route::post('/dang-xuat', [AuthController::class, 'logout'])->name('logout');

    Route::get('/gio-hang', [CartController::class, 'index'])->name('cart.index');
    Route::post('/gio-hang', [CartController::class, 'store'])->name('cart.store');
    Route::patch('/gio-hang/{cartItem}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/gio-hang/{cartItem}', [CartController::class, 'destroy'])->name('cart.destroy');

    Route::post('/dat-hang', [OrderController::class, 'store'])->name('orders.store');
    Route::get('/don-hang', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/don-hang/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::patch('/don-hang/{order}/huy', [OrderController::class, 'cancel'])->name('orders.cancel');

    Route::get('/tai-khoan', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/tai-khoan', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/tai-khoan/mat-khau', [ProfileController::class, 'password'])->name('profile.password');

    Route::post('/mon/{product}/danh-gia', [ReviewController::class, 'store'])->name('reviews.store');

    Route::get('/thanh-toan/{order}', [PaymentController::class, 'show'])->name('payment.show');
    Route::post('/thanh-toan/{order}/da-chuyen-khoan', [PaymentController::class, 'confirmTransfer'])->name('payment.transfer');
    Route::post('/thanh-toan/{order}/momo', [PaymentController::class, 'momo'])->name('payment.momo');

    Route::get('/thong-bao', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/thong-bao/cap-nhat', [NotificationController::class, 'poll'])->name('notifications.poll');
    Route::post('/thong-bao/doc-het', [NotificationController::class, 'readAll'])->name('notifications.readAll');
    Route::get('/thong-bao/{id}', [NotificationController::class, 'open'])->name('notifications.open');

    Route::get('/chat/tin-nhan', [ChatController::class, 'messages'])->name('chat.messages');
    Route::post('/chat/tin-nhan', [ChatController::class, 'send'])->middleware('throttle:20,1')->name('chat.send');
});

// ── Quản trị (phải đăng nhập + role admin) ──
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::resource('categories', Admin\CategoryController::class)->except(['show', 'create']);
    Route::resource('products', Admin\ProductController::class)->except('show');
    Route::get('orders', [Admin\OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [Admin\OrderController::class, 'show'])->name('orders.show');
    Route::patch('orders/{order}', [Admin\OrderController::class, 'update'])->name('orders.update');
    Route::post('orders/{order}/xac-nhan-tien', [Admin\OrderController::class, 'confirmPayment'])->name('orders.confirmPayment');

    Route::get('chat/{user?}', [Admin\ChatController::class, 'index'])->name('chat.index');
    Route::get('chat/{user}/tin-nhan', [Admin\ChatController::class, 'messages'])->name('chat.messages');
    Route::post('chat/{user}/tin-nhan', [Admin\ChatController::class, 'send'])->name('chat.send');
    Route::get('users', [Admin\UserController::class, 'index'])->name('users.index');
});
