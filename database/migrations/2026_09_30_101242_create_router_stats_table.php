<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('router_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('router_id')->constrained()->onDelete('cascade');
            $table->float('cpu_load')->default(0);
            $table->float('memory_used')->default(0);
            $table->float('memory_total')->default(0);
            $table->float('hdd_used')->default(0);
            $table->float('hdd_total')->default(0);
            $table->string('uptime')->nullable();
            $table->string('version')->nullable();
            $table->integer('active_connections')->default(0);
            $table->timestamp('recorded_at');
            $table->timestamps();
            
            $table->index(['router_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('router_stats');
    }
};
