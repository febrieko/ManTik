<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotspot_vouchers', function (Blueprint $table) {
            // Tambah kolom username & password untuk integrasi MikroTik
            // (jika belum ada)
            if (!Schema::hasColumn('hotspot_vouchers', 'username')) {
                $table->string('username', 100)->nullable()->after('code')
                    ->comment('Username untuk login MikroTik hotspot');
            }
            if (!Schema::hasColumn('hotspot_vouchers', 'password')) {
                $table->string('password', 100)->nullable()->after('username')
                    ->comment('Password untuk login MikroTik hotspot');
            }
        });
    }

    public function down(): void
    {
        Schema::table('hotspot_vouchers', function (Blueprint $table) {
            $table->dropColumnIfExists('username');
            $table->dropColumnIfExists('password');
        });
    }
};
