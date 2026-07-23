<?php
$file = 'c:/xampp/htdocs/hrm_rising/app/Http/Controllers/EmployeeController.php';
$content = file_get_contents($file);

$oldCheck = <<<OLD
                // o. Prevent employees from updating after approval
                if (\Auth::user()->type === 'employee' && \$employee->approval_status === 'approved') {
                    return redirect()->back()->with('error', __('Your details have been approved and can no longer be edited.'));
                }
OLD;

$newCheck = <<<NEW
                // o. Prevent employees from updating after approval
                if (\Auth::user()->type === 'employee' && \$employee->approval_status === 'approved' && !\Auth::user()->isHR()) {
                    return redirect()->back()->with('error', __('Your details have been approved and can no longer be edited.'));
                }
NEW;

$content = str_replace($oldCheck, $newCheck, $content);

file_put_contents($file, $content);
echo "EmployeeController patched for update method!\n";
