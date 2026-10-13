<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('traffic_hourly_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('router_id')->constrained()->cascadeOnDelete();
            $table->string('interface', 100);
            $table->timestamp('hour_at');          // Jam yang dirangkum (misal 2026-10-07 14:00:00)
            $table->bigInteger('avg_rx_bps')->default(0);
            $table->bigInteger('max_rx_bps')->default(0);
            $table->bigInteger('avg_tx_bps')->default(0);
            $table->bigInteger('max_tx_bps')->default(0);
            $table->integer('sample_count')->default(0);
            $table->timestamps();

            $table->unique(['router_id', 'interface', 'hour_at']);
            $table->index(['router_id', 'interface', 'hour_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('traffic_hourly_stats');
    }
};
