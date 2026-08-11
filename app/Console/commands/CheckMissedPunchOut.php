<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AttendanceEmployee;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CheckMissedPunchOut extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:check-missed-punchout';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for employees who punched in 9+ hours ago and have not punched out yet, and notify them.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for missed punch-outs (>= 9 hours)...');

        $now = Carbon::now();

        // Query attendance records with valid clock_in but missing clock_out
        $attendances = AttendanceEmployee::whereNotNull('clock_in')
            ->where('clock_in', '!=', '00:00:00')
            ->where(function ($query) {
                $query->whereNull('clock_out')
                    ->orWhere('clock_out', '=', '')
                    ->orWhere('clock_out', '=', '00:00:00');
            })
            ->whereNull('missed_punchout_notified_at')
            ->get();

        $count = 0;

        foreach ($attendances as $attendance) {
            try {
                $clockInTimeStr = $attendance->clock_in;
                $dateStr = $attendance->date;
                $clockInDateTime = Carbon::parse($dateStr . ' ' . $clockInTimeStr);

                // Calculate difference in minutes from punch in time to now
                $diffInMinutes = $clockInDateTime->diffInMinutes($now, false);

                // 9 hours = 540 minutes
                if ($diffInMinutes >= 540) {
                    $attendance->missed_punchout_notified_at = $now;
                    $attendance->save();

                    $employee = $attendance->employee;
                    $empName = $employee ? $employee->full_name : 'Employee #' . $attendance->employee_id;

                    // Trigger database notification to employee user
                    if ($employee && $employee->user) {
                        try {
                            $employee->user->notify(new \App\Notifications\MissedPunchOutNotification([
                                'title' => 'Missed Punch-Out Alert',
                                'message' => 'You missed your punch out.',
                                'attendance_id' => $attendance->id,
                                'date' => $attendance->date,
                                'clock_in' => $attendance->clock_in,
                                'url' => route('attendanceemployee.index'),
                            ]));
                        } catch (\Exception $notifException) {
                            Log::error("Failed to send notification to user {$employee->user_id}: " . $notifException->getMessage());
                        }
                    }

                    Log::warning("Missed Punch-Out Alert: Employee {$empName} (ID: {$attendance->employee_id}) punched in at {$clockInDateTime->toDateTimeString()} and has not punched out after 9 hours.");

                    $this->info("Notified missed punch-out for {$empName}");
                    $count++;
                }
            } catch (\Exception $e) {
                Log::error("Error processing missed punch-out for attendance ID {$attendance->id}: " . $e->getMessage());
            }
        }

        $this->info("Completed check. Notified {$count} employee(s).");
        return 0;
    }
}
