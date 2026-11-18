<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            // Mỗi khách có 1 cuộc trò chuyện với CSKH: user_id = khách sở hữu cuộc trò chuyện
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_staff')->default(false); // true = nhân viên CSKH gửi
            $table->text('body');
            $table->timestamp('read_at')->nullable();    // bên nhận đã đọc chưa
            $table->timestamps();
            $table->index(['user_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
