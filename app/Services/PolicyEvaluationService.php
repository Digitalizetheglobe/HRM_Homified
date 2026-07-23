<?php

namespace App\Services;

use Carbon\Carbon;

class PolicyEvaluationService
{
    /**
     * Evaluate Sandwich Leave and Week-Off between leaves.
     * Rule: If a week-off falls immediately before or after a leave day, or between two leave days, 
     * it converts to LOP (or Leave). We will default to LOP for adjacent unapproved/unpaid, 
     * and if both sides are leave, it converts to LOP/Leave as per policy.
     *
     * @param string $date The date to evaluate (Y-m-d)
     * @param array $statusCodes The array of status codes for the month
     * @return string The new status code for this day
     */
    public static function evaluateSandwichRule($date, $statusCodes)
    {
        $currentStatus = $statusCodes[$date] ?? '';
        
        // Only evaluate if current day is a Week-Off
        if ($currentStatus !== 'WO') {
            return $currentStatus;
        }

        $parsedDate = Carbon::parse($date);
        $prevDay = $parsedDate->copy()->subDay()->format('Y-m-d');
        $nextDay = $parsedDate->copy()->addDay()->format('Y-m-d');

        $prevStatus = $statusCodes[$prevDay] ?? null;
        $nextStatus = $statusCodes[$nextDay] ?? null;

        $isPrevLeave = in_array($prevStatus, ['LOP', 'SL', 'EL', 'Leave']); // 'Leave' is generic
        $isNextLeave = in_array($nextStatus, ['LOP', 'SL', 'EL', 'Leave']);

        if ($isPrevLeave || $isNextLeave) {
            // Policy 1 & 2: If leave is taken before OR after week-off, it becomes LOP.
            return 'LOP';
        }

        return $currentStatus;
    }

    /**
     * Evaluate Friday / Saturday / Sunday leave rule.
     * Rule: Leave taken on Friday, Saturday, or Sunday = 2 days of LOP unless approved under special circumstances.
     * 
     * @param string $date The date of the leave
     * @param bool $isSpecialApproval Whether this leave has special approval
     * @return int Number of extra LOP days to deduct (0 if no penalty, 1 extra if penalty applies)
     */
    public static function evaluateWeekendLeavePenalty($date, $isSpecialApproval = false)
    {
        if ($isSpecialApproval) {
            return 0; // No penalty
        }

        $dayOfWeek = strtolower(Carbon::parse($date)->format('l'));
        if (in_array($dayOfWeek, ['friday', 'saturday', 'sunday'])) {
            return 1; // 1 extra LOP day
        }

        return 0;
    }

    /**
     * Evaluate Late Mark Policy.
     * Rule: 15-min grace period. Every 3 late marks = 0.5 day deduction.
     *
     * @param \Illuminate\Support\Collection|array $attendanceRecords The array of attendance records for the month
     * @return float The total LOP days to deduct for late marks
     */
    public static function evaluateLateMarkDeduction($attendanceRecords)
    {
        $lateCount = 0;
        
        foreach ($attendanceRecords as $attendance) {
            if (!empty($attendance->late)) {
                $lateParts = explode(':', $attendance->late);
                if (count($lateParts) == 3) {
                    $lateMinutes = (intval($lateParts[0]) * 60) + intval($lateParts[1]);
                    // If late by more than 15 minutes
                    if ($lateMinutes > 15) {
                        $lateCount++;
                    }
                }
            }
        }

        // 0.5 day deduction for every 3 late marks
        return floor($lateCount / 3) * 0.5;
    }

    /**
     * Evaluate Leave Approval Process.
     * Rule: Unapproved leaves (status != Approved) automatically convert to LOP.
     * 
     * @param object $leave The leave record
     * @return bool True if it should be treated as LOP
     */
    public static function isUnapprovedLeave($leave)
    {
        return strtolower(trim($leave->status)) !== 'approved';
    }
}
