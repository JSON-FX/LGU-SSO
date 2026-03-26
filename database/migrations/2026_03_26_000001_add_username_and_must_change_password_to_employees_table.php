<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'username')) {
                $table->string('username')->unique()->after('email');
            }
            if (!Schema::hasColumn('employees', 'must_change_password')) {
                $table->boolean('must_change_password')->default(true)->after('password');
            }
        });

        // Generate usernames for existing employees
        $employees = DB::table('employees')->get();
        foreach ($employees as $employee) {
            $firstInitial = strtolower(substr($employee->first_name, 0, 1));
            $lastName = strtolower(str_replace(' ', '', $employee->last_name));
            $baseUsername = "{$firstInitial}.{$lastName}";
            $username = $baseUsername;
            $counter = 1;

            while (DB::table('employees')->where('username', $username)->where('id', '!=', $employee->id)->exists()) {
                $counter++;
                $username = "{$baseUsername}{$counter}";
            }

            DB::table('employees')->where('id', $employee->id)->update([
                'username' => $username,
                'must_change_password' => false, // existing employees don't need to change
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['username', 'must_change_password']);
        });
    }
};
