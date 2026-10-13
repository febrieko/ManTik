<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotspot_vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('router_id')->constrained()->onDelete('cascade');
            $table->foreignId('package_id')->nullable()->constrained()->onDelete('set null');
            $table->string('code')->unique();
            $table->string('profile')->nullable();
            $table->string('bandwidth_limit')->nullable()->comment('e.g. 5M/5M');
            $table->integer('duration_minutes')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->boolean('is_used')->default(false);
            $table->string('used_by')->nullable()->comment('Username or device that used it');
            $table->timestamp('used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotspot_vouchers');
    }
};
