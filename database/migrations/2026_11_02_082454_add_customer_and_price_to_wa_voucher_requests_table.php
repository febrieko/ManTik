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
        Schema::table('wa_voucher_requests', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('phone')->constrained('customers')->nullOnDelete();
            $table->decimal('price_deducted', 12, 2)->default(0)->after('voucher_code')->comment('Jumlah saldo yang dipotong');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wa_voucher_requests', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropColumn(['customer_id', 'price_deducted']);
        });
    }
};
