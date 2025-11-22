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
        Schema::table('base_sistem', function (Blueprint $table) {
            $table->unsignedBigInteger('akun_lr_berjalan_id')->nullable()->after('logo');
            $table->unsignedBigInteger('akun_lr_lalu_id')->nullable()->after('akun_lr_berjalan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('base_sistem', function (Blueprint $table) {
            $table->dropColumn(['akun_lr_berjalan_id', 'akun_lr_lalu_id']);
        });
    }
};
