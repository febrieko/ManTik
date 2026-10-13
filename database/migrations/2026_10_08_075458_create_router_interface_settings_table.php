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
        Schema::create('router_interface_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('router_id')->constrained('routers')->cascadeOnDelete();
            $table->string('interface_name', 100);
            $table->string('display_name', 150)->nullable();
            $table->boolean('is_visible')->default(true);
            $table->smallInteger('sort_order')->default(0);
            $table->string('description', 255)->nullable();
            $table->timestamps();

            $table->unique(['router_id', 'interface_name']);
            $table->index(['router_id', 'is_visible', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('router_interface_settings');
    }
};
