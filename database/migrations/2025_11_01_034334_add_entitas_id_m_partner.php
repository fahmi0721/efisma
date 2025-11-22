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
        Schema::table('m_partner', function (Blueprint $table) {
            $table->unsignedBigInteger('entitas_id')->index();

            $table->foreign('entitas_id')->references('id')->on('m_entitas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('m_partner', function (Blueprint $table) {
            //
        });
    }
};
