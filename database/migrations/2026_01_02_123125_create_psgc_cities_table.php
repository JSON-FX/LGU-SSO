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
        Schema::create('psgc_cities', function (Blueprint $table) {
            $table->string('code', 10)->primary();
            $table->string('name');
            $table->string('province_code', 10)->index();

            $table->foreign('province_code')->references('code')->on('psgc_provinces')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('psgc_cities');
    }
};
