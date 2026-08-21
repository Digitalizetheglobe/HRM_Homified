<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_employees', function (Blueprint $table) {
            if (!Schema::hasColumn('attendance_employees', 'status_reason')) {
                $table->string('status_reason', 64)->nullable()->after('status');
            }
            if (!Schema::hasColumn('attendance_employees', 'late_cycle_number')) {
                $table->unsignedTinyInteger('late_cycle_number')->nullable()->after('late');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendance_employees', function (Blueprint $table) {
            if (Schema::hasColumn('attendance_employees', 'status_reason')) {
                $table->dropColumn('status_reason');
            }
            if (Schema::hasColumn('attendance_employees', 'late_cycle_number')) {
                $table->dropColumn('late_cycle_number');
            }
        });
    }
};
