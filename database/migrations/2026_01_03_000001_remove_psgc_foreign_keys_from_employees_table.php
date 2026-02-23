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
        // No-op: FK constraints were never created (PSGC tables don't exist in this deployment)
        // Location codes are stored as plain strings and resolved via PSGC API
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: Reversing would require the PSGC tables to exist
        // This is intentionally left empty as we're moving away from local PSGC tables
    }
};
