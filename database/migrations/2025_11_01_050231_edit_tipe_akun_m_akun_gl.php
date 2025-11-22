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
        Schema::table('m_akun_gl', function (Blueprint $table) {
            $table->enum('tipe_akun', ['aktiva', 'pasiva', 'modal', 'pendapatan', 'beban'])
          ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('m_akun_gl', function (Blueprint $table) {
            
        });
    }
};
