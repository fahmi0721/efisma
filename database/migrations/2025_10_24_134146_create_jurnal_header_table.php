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
        Schema::create('jurnal_header', function (Blueprint $table) {
            $table->id();
            $table->string('kode_jurnal', 30)->unique();
            $table->enum('jenis', ['JP', 'JKM', 'JKK', 'JN'])->comment('JP=Pendapatan, JKM=Kas Masuk, JKK=Kas Keluar, JN=Penyesuaian');
            $table->date('tanggal');
            $table->text('keterangan')->nullable();

            $table->unsignedBigInteger('entitas_id')->index();
            $table->unsignedBigInteger('partner_id')->nullable()->index();

            $table->decimal('total_debit', 18, 2)->default(0);
            $table->decimal('total_kredit', 18, 2)->default(0);

            $table->enum('status', ['draft', 'posted', 'void'])->default('draft');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->timestamp('posted_at')->nullable();

            $table->timestamps();

            // foreign keys (optional: sesuaikan nama tabel master kamu)
            $table->foreign('entitas_id')->references('id')->on('m_entitas');
            $table->foreign('partner_id')->references('id')->on('m_partner');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jurnal_header');
    }
};
