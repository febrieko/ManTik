<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('bandwidth_up')->nullable()->comment('Upload speed e.g. 10M');
            $table->string('bandwidth_down')->nullable()->comment('Download speed e.g. 20M');
            $table->decimal('price', 12, 2)->default(0);
            $table->integer('duration_days')->default(30)->comment('Durasi paket dalam hari');
            $table->enum('type', ['pppoe', 'hotspot', 'static'])->default('pppoe');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
