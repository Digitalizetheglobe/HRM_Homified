<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CompOffLeave;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ResetCompOffForNewYear extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'comp-off:reset-new-year 
                            {--year= : Specific year to reset (default: current year)}
                            {--force : Force reset without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset all Comp-Off balances for new year. Employees who work on week-off days will still get Comp-Off automatically.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $year = $this->option('year') ?: Carbon::now()->year;
        $force = $this->option('force');

        $this->info("═══════════════════════════════════════════════════════");
        $this->info("  Comp-Off Reset for New Year {$year}");
        $this->info("═══════════════════════════════════════════════════════");
        $this->newLine();

        // Count existing Comp-Off records
        $totalRecords = CompOffLeave::count();
        
        if ($totalRecords == 0) {
            $this->info("ℹ️  No Comp-Off records found. Nothing to reset.");
            return 0;
        }

        // Count by employee
        $recordsByEmployee = DB::table('comp_off_leaves')
            ->select('employees_id', DB::raw('count(*) as total'))
            ->groupBy('employees_id')
            ->get();

        $this->info("📊 Current Comp-Off Status:");
        $this->info("   Total Records: {$totalRecords}");
        $this->info("   Employees with Comp-Off: " . $recordsByEmployee->count());
        $this->newLine();

        // Show summary by employee
        if ($recordsByEmployee->count() > 0 && $recordsByEmployee->count() <= 20) {
            $this->info("📋 Comp-Off Balance by Employee:");
            foreach ($recordsByEmployee as $record) {
                $employee = \App\Models\Employee::find($record->employees_id);
                $employeeName = $employee ? $employee->full_name : "Employee ID: {$record->employees_id}";
                $this->line("   • {$employeeName}: {$record->total} day(s)");
            }
            $this->newLine();
        }

        // Confirmation
        if (!$force) {
            $this->warn("⚠️  WARNING: This will DELETE ALL Comp-Off records!");
            $this->warn("   Employees who work on week-off days will still get Comp-Off automatically.");
            $this->newLine();
            
            if (!$this->confirm('Do you want to proceed with resetting all Comp-Off records?', false)) {
                $this->info("❌ Reset cancelled.");
                return 0;
            }
        }

        // Perform reset
        $this->info("🔄 Resetting Comp-Off records...");
        
        try {
            $deleted = DB::table('comp_off_leaves')->delete();
            
            $this->newLine();
            $this->info("✅ Successfully reset Comp-Off balances!");
            $this->info("   Deleted: {$deleted} record(s)");
            $this->newLine();
            
            $this->info("📝 Notes:");
            $this->info("   • All Comp-Off balances have been reset to 0");
            $this->info("   • The automatic Comp-Off system will continue to work");
            $this->info("   • Employees who work on their week-off days will still get Comp-Off");
            $this->info("   • Run 'php artisan comp-off:process' to process new Comp-Offs");
            $this->newLine();
            
            return 0;
        } catch (\Exception $e) {
            $this->error("❌ Error resetting Comp-Off records: " . $e->getMessage());
            return 1;
        }
    }
}
