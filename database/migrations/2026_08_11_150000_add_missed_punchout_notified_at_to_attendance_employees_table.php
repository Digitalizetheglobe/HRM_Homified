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
        if (Schema::hasTable('attendance_employees')) {
            Schema::table('attendance_employees', function (Blueprint $table) {
                if (!Schema::hasColumn('attendance_employees', 'missed_punchout_notified_at')) {
                    $table->timestamp('missed_punchout_notified_at')->nullable()->after('clock_out');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('attendance_employees')) {
            Schema::table('attendance_employees', function (Blueprint $table) {
                if (Schema::hasColumn('attendance_employees', 'missed_punchout_notified_at')) {
                    $table->dropColumn('missed_punchout_notified_at');
                }
            });
        }
    }
};
