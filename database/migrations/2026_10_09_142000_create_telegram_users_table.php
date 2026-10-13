<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_users', function (Blueprint $table) {
            $table->id();
            $table->string('chat_id')->unique()->comment('ID unik dari Telegram');
            $table->string('username')->nullable()->comment('Username Telegram');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->decimal('balance', 12, 2)->default(0)->comment('Saldo deposit untuk beli voucher');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_users');
    }
};
