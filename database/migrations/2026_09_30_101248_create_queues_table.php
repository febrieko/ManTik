<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('router_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('target')->nullable()->comment('Target IP/Interface');
            $table->string('dst')->nullable();
            $table->string('max_limit')->nullable()->comment('Max bandwidth e.g. 10M/10M');
            $table->string('burst_limit')->nullable();
            $table->string('burst_threshold')->nullable();
            $table->integer('priority')->default(8);
            $table->enum('type', ['simple', 'tree'])->default('simple');
            $table->string('parent')->nullable()->comment('For queue tree parent');
            $table->boolean('is_disabled')->default(false);
            $table->string('comment')->nullable();
            $table->string('mikrotik_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queues');
    }
};
