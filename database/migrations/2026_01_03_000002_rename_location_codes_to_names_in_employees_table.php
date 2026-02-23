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
        Schema::table('employees', function (Blueprint $table) {
            // Rename code columns to name columns and increase size for full names
            $table->renameColumn('region_code', 'region');
            $table->renameColumn('province_code', 'province');
            $table->renameColumn('city_code', 'city');
            $table->renameColumn('barangay_code', 'barangay');
        });

        // Modify column types to accommodate full names
        Schema::table('employees', function (Blueprint $table) {
            $table->string('region', 255)->nullable()->change();
            $table->string('province', 255)->nullable()->change();
            $table->string('city', 255)->nullable()->change();
            $table->string('barangay', 255)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('region', 10)->nullable()->change();
            $table->string('province', 10)->nullable()->change();
            $table->string('city', 10)->nullable()->change();
            $table->string('barangay', 10)->nullable()->change();
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->renameColumn('region', 'region_code');
            $table->renameColumn('province', 'province_code');
            $table->renameColumn('city', 'city_code');
            $table->renameColumn('barangay', 'barangay_code');
        });
    }
};
