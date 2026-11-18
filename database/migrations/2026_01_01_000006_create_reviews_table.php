<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating'); // 1..5 sao
            $table->string('comment', 500)->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'product_id']); // mỗi khách đánh giá 1 món 1 lần (sửa được)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
