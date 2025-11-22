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
        Schema::create('m_partner', function (Blueprint $table) {
            $table->id();
            $table->string('kode',10)->nullable();
            $table->string('nama');
            $table->string('alamat')->nullable();
            $table->string('no_telpon')->nullable();
            $table->enum('is_vendor',['active','inactive'])->default('inactive');
            $table->enum('is_customer',['active','inactive'])->default('inactive');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('m_partner');
    }
};
