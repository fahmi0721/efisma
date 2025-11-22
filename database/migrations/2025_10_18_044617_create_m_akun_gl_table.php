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
        Schema::create('m_akun_gl', function (Blueprint $table) {
            $table->id();
            $table->string("no_akun",9);
            $table->string("nama");
            $table->enum("tipe_akun",['aktiva','pasiva','pendapatan','beban'])->default('aktiva');
            $table->enum("saldo_normal",['debet','kredit'])->default('debet');
            $table->bigInteger("parent_id")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('m_akun_gl');
    }
};
