<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add contact fields to users
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->string('telegram_chat_id')->nullable()->after('phone');
        });

        // 2. Add contact & balance fields to customers
        Schema::table('customers', function (Blueprint $table) {
            $table->string('telegram_chat_id')->nullable()->after('phone');
            $table->decimal('balance', 12, 2)->default(0)->after('status')->comment('Saldo deposit pelanggan');
        });

        // 3. Make notification_logs type and channel flexible
        Schema::table('notification_logs', function (Blueprint $table) {
            $table->string('type', 50)->change();
            $table->string('channel', 50)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'telegram_chat_id']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['telegram_chat_id', 'balance']);
        });
    }
};
