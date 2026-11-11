<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('topup_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('reference_code', 60)->unique()->comment('Kode unik transaksi top-up, misal TOPUP-20261111-XXXX');
            $table->decimal('amount', 12, 2)->comment('Nominal saldo yang diterima pelanggan');
            $table->decimal('fee', 12, 2)->default(0)->comment('Biaya admin / payment gateway');
            $table->decimal('total_amount', 12, 2)->comment('Total yang harus dibayar (amount + fee)');
            $table->string('gateway', 50)->default('manual')->comment('manual, tripay, midtrans, xendit, duitku');
            $table->string('payment_method', 50)->nullable()->comment('admin_cash, bank_transfer, qris, va_bca, dll');
            $table->string('gateway_reference', 120)->nullable()->comment('ID/Reference dari payment gateway');
            $table->text('payment_url')->nullable()->comment('URL checkout payment gateway');
            $table->text('qr_url')->nullable()->comment('URL/String QRIS jika tersedia');
            $table->enum('status', ['pending', 'paid', 'expired', 'failed'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->json('payload')->nullable()->comment('Raw callback/response dari gateway');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['customer_id', 'status']);
            $table->index(['gateway', 'gateway_reference']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('topup_transactions');
    }
};
