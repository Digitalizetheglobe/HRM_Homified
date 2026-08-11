<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckCompOff extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-comp-off';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $employees = \App\Models\Employee::has('compOffLeaves')->with('compOffLeaves')->get();
        
        $this->table(
            ['Employee ID', 'Name', 'Comp Off Date', 'Comp Off Count', 'Total Available'],
            $employees->map(function($employee) {
                $total = $employee->compOffLeaves->sum('comp_off_data');
                return [
                    $employee->id,
                    $employee->name,
                    $employee->compOffLeaves->pluck('comp_off_date')->join(', '),
                    $employee->compOffLeaves->pluck('comp_off_data')->join(', '),
                    $total
                ];
            })
        );
    }
}
