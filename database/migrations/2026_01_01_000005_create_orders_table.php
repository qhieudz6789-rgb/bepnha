<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('customer_name');
            $table->string('phone', 20);
            $table->string('address');
            $table->string('note')->nullable();
            $table->unsignedInteger('total_price')->default(0);
            $table->enum('status', ['pending', 'confirmed', 'preparing', 'delivering', 'delivered', 'cancelled'])
                ->default('pending');
            // Thanh toán: cod | vietqr | momo — unpaid | pending (khách báo đã CK, chờ quán kiểm tra) | paid | refund
            $table->string('payment_method', 20)->default('cod');
            $table->string('payment_status', 20)->default('unpaid');
            $table->string('payment_ref', 64)->nullable()->unique(); // mã giao dịch gửi sang MoMo
            $table->timestamp('paid_at')->nullable();
            // Shipper nhận giao đơn
            $table->string('shipper_name', 100)->nullable();
            $table->string('shipper_phone', 20)->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            // Món bị xoá thì đơn cũ vẫn giữ tên + giá đã lưu
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->unsignedInteger('price');
            $table->unsignedInteger('quantity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
