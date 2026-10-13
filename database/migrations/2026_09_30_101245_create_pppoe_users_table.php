<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pppoe_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('router_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('package_id')->nullable()->constrained()->onDelete('set null');
            $table->string('username')->unique();
            $table->string('password');
            $table->string('profile')->nullable()->comment('PPPoE Profile name on MikroTik');
            $table->string('service')->default('pppoe');
            $table->string('local_address')->nullable();
            $table->string('remote_address')->nullable();
            $table->boolean('is_disabled')->default(false);
            $table->string('comment')->nullable();
            $table->string('mikrotik_id')->nullable()->comment('.id from RouterOS');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['router_id', 'username']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pppoe_users');
    }
};
