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
        Schema::create('pelunasan_piutang', function (Blueprint $table) {
            $table->id();
            
            // Relasi ke jurnal kas (JKM)
            $table->unsignedBigInteger('jurnal_kas_id')->nullable()
                  ->comment('ID jurnal kas masuk (JKM)');
            
            // Relasi ke jurnal piutang (JP)
            $table->unsignedBigInteger('jurnal_piutang_id')->nullable()
                  ->comment('ID jurnal pendapatan (JP)');

            // Nilai pelunasan
            $table->decimal('jumlah', 18, 2)->default(0)
                  ->comment('Jumlah pelunasan piutang');

            $table->timestamps();

            // (Opsional) Foreign key jika tabel jurnal sudah ada
            // 🔗 Relasi ke tabel jurnal yang sama
            $table->foreign('jurnal_kas_id')
                ->references('id')
                ->on('jurnal_header')
                ->onDelete('cascade');

            $table->foreign('jurnal_piutang_id')
                ->references('id')
                ->on('jurnal_header')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pelunasan_piutang');
    }
};
