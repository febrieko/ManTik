<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->enum('customer_type', ['regular', 'reseller'])
                ->default('regular')
                ->after('status')
                ->comment('Jenis pelanggan: regular atau reseller voucher');
            $table->unique('phone', 'customers_phone_unique');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->unique('phone', 'users_phone_unique');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique('customers_phone_unique');
            $table->dropColumn('customer_type');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_phone_unique');
            $table->string('email')->nullable(false)->change();
        });
    }
};
