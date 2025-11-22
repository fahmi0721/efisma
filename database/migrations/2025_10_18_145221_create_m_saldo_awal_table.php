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
        Schema::create('m_saldo_awal', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('akun_gl_id');
            $table->string('periode', 7); // ganti dari tahun ke periode (YYYY-MM)
            $table->unsignedBigInteger('entitas_id');
            $table->decimal('saldo', 20, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saldo_awal');
    }
};
