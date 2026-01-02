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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('suffix')->nullable();
            $table->date('birthday');
            $table->string('civil_status');
            $table->string('province_code', 10)->nullable();
            $table->string('city_code', 10)->nullable();
            $table->string('barangay_code', 10)->nullable();
            $table->string('residence');
            $table->string('block_number')->nullable();
            $table->string('building_floor')->nullable();
            $table->string('house_number')->nullable();
            $table->string('nationality');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('province_code')->references('code')->on('psgc_provinces')->nullOnDelete();
            $table->foreign('city_code')->references('code')->on('psgc_cities')->nullOnDelete();
            $table->foreign('barangay_code')->references('code')->on('psgc_barangays')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
