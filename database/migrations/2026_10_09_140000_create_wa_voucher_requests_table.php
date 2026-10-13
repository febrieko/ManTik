<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_voucher_requests', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20)->comment('Nomor WA pengirim (format internasional, e.g. 6281234567890)');
            $table->string('requester_name', 100)->nullable()->comment('Nama pengunjung dari format pesan');
            $table->string('package_code', 50)->nullable()->comment('Kode paket yang diminta');
            $table->foreignId('package_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('voucher_id')->nullable()->constrained('hotspot_vouchers')->onDelete('set null');
            $table->string('voucher_code', 50)->nullable()->comment('Kode voucher yang diberikan');
            $table->enum('status', ['success', 'failed', 'pending'])->default('pending');
            $table->string('failure_reason', 255)->nullable()->comment('Alasan kegagalan jika status = failed');
            $table->text('raw_message')->nullable()->comment('Pesan asli dari pengunjung');
            $table->timestamps();

            // Index untuk pencarian cepat
            $table->index('phone');
            $table->index('status');
            $table->index('created_at');
            $table->index(['phone', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_voucher_requests');
    }
};
