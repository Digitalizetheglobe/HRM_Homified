<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AttendanceEmployee;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AutoCheckoutMissedPunches extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:auto-checkout-missed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically checkout missed punches after 5 hours and mark as Half Day';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();
        $this->info("Running auto-checkout at " . $now->toDateTimeString());

        // Look for open punch-ins (clock_out is 00:00:00 or null)
        $openAttendances = AttendanceEmployee::where(function($query) {
                $query->where('clock_out', '00:00:00')
                      ->orWhereNull('clock_out');
            })
            ->whereNotNull('clock_in')
            ->where('clock_in', '!=', '00:00:00')
            ->get();

        $count = 0;
        foreach ($openAttendances as $attendance) {
            try {
                $clockInTime = Carbon::parse($attendance->date . ' ' . $attendance->clock_in);
                
                // Check if 5 hours have passed since clock-in
                // If it's a future date or time parsing issue, skip
                if ($now->greaterThanOrEqualTo($clockInTime) && $now->diffInMinutes($clockInTime) >= (5 * 60)) {
                    // Set clock_out to clock_in + 5 hours
                    $clockOutTime = $clockInTime->copy()->addHours(5);
                    
                    $attendance->clock_out = $clockOutTime->format('H:i:s');
                    app(\App\Services\AttendanceRuleService::class)->applyAndSave($attendance, [
                        'employee' => $attendance->employee,
                        'force_reason' => AttendanceEmployee::REASON_HALF_DAY_MISSING_PUNCH_OUT,
                    ]);
                    $count++;
                }
            } catch (\Exception $e) {
                Log::error("AutoCheckoutMissedPunches Error on Attendance ID {$attendance->id}: " . $e->getMessage());
            }
        }

        $this->info("Checked out {$count} missed punches.");
        Log::info("AutoCheckoutMissedPunches: Checked out {$count} missed punches.");
    }
}
