<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('traffic_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('router_id')->constrained()->cascadeOnDelete();
            $table->string('interface', 100);
            $table->bigInteger('rx_bytes')->default(0); // Total RX Bytes
            $table->bigInteger('tx_bytes')->default(0); // Total TX Bytes
            $table->bigInteger('rx_bps')->default(0);   // Current RX Bits/s
            $table->bigInteger('tx_bps')->default(0);   // Current TX Bits/s
            $table->timestamp('recorded_at')->useCurrent();
            
            // Indexing untuk mempercepat query saat memfilter berdasarkan waktu, router, dan interface
            $table->index(['router_id', 'interface', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('traffic_logs');
    }
};
