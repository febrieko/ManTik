<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('public_voucher_orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference_code', 50)->unique();
            $table->foreignId('package_id')->constrained('packages')->cascadeOnDelete();
            $table->foreignId('router_id')->nullable()->constrained('routers')->nullOnDelete();
            $table->foreignId('voucher_id')->nullable()->constrained('hotspot_vouchers')->nullOnDelete();
            $table->string('buyer_name', 100)->default('Tamu Hotspot');
            $table->string('buyer_phone', 30)->nullable()->index();
            $table->decimal('amount', 12, 2);
            $table->decimal('fee', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            $table->string('gateway', 40)->default('manual');
            $table->string('payment_method', 60)->nullable();
            $table->string('gateway_reference', 120)->nullable()->index();
            $table->text('payment_url')->nullable();
            $table->text('qr_url')->nullable();
            $table->string('status', 30)->default('pending')->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->json('payload')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_voucher_orders');
    }
};

