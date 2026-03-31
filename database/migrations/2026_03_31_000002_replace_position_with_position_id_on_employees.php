<?php

use App\Models\Position;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('position_id')->nullable()->after('office_id')->constrained()->nullOnDelete();
        });

        $distinctPositions = DB::table('employees')
            ->whereNotNull('position')
            ->where('position', '!=', '')
            ->distinct()
            ->pluck('position');

        foreach ($distinctPositions as $title) {
            $position = Position::firstOrCreate(['title' => $title]);
            DB::table('employees')
                ->where('position', $title)
                ->update(['position_id' => $position->id]);
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('position');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('position')->nullable()->after('office_id');
        });

        $employees = DB::table('employees')->whereNotNull('position_id')->get(['id', 'position_id']);
        foreach ($employees as $emp) {
            $position = DB::table('positions')->find($emp->position_id);
            if ($position) {
                DB::table('employees')->where('id', $emp->id)->update(['position' => $position->title]);
            }
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['position_id']);
            $table->dropColumn('position_id');
        });
    }
};
