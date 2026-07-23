<?php

namespace App\Http\Controllers;
use App\Models\Unit;
use App\Models\Employee;
use App\Models\TimeSheet;
use App\Models\Project;
use App\Models\User;
use App\Models\Utility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TimeSheetExport;
use App\Mail\FollowUpReminder;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class TimeSheetController extends Controller
{
    /**
     * Subquery to exclude terminated employees (employees existing in `terminations` table).
     */
    protected function terminatedEmployeeSubquery($query)
    {
        return $query->select('employee_id')->from('terminations');
    }
    
    public function index(Request $request)
    {
        $query = TimeSheet::with(['employee.employee', 'project', 'assignedEmployee.employee']);

        if (Auth::user()->type == 'employee') {
                $userId = Auth::id();
                $employeeId = Auth::user()->employee->id ?? null;
                
                $query->where(function($q) use ($userId, $employeeId) {
                    // Timesheets where current user is the assigned_to employee
                    $q->where('assigned_to', $userId)
                    
                    // OR timesheets where current user is the creator AND not assigned to anyone
                    ->orWhere(function($subQ) use ($userId) {
                        $subQ->where('employee_id', $userId)
                            ->whereNull('assigned_to');
                    })
                    
                    // OR timesheets for projects where user is site head (read-only access)
                    ->orWhereHas('project', function($projectQuery) use ($employeeId) {
                        $projectQuery->where(function($pq) use ($employeeId) {
                            $pq->whereJsonContains('site_heads', (string)$employeeId)
                                ->orWhereJsonContains('site_heads', $employeeId)
                                ->orWhereJsonContains('site_heads', (int)$employeeId);
                        });
                    });
                });
            }   

        // Rest of your filter code remains the same...
        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        }

        if ($request->filled('project') && $request->project != '') {
            $query->where('project_id', $request->project);
        }

        if ($request->filled('client_status') && $request->client_status != 'Select Client Status') {
            $query->where('client_status', $request->client_status);
        }

        // Get results ordered by latest first
        $timeSheets = $query->latest()->get();

        // Store filtered timesheets in session for export
        session(['export_timesheets' => $timeSheets]);

        $employeesList = Employee::pluck('name', 'id')->prepend(__('Select Employee'), '');
        
        // Modified projects list query for site heads (using employee ID)
        if (Auth::user()->type == 'employee') {
            $userId = Auth::id();
            $employeeId = Auth::user()->employee->id ?? null;
            
            $projectsQuery = Project::query();
            
            // Site head projects (using employee ID)
            if ($employeeId) {
                $projectsQuery->where(function($q) use ($employeeId) {
                    $q->whereJsonContains('site_heads', (string)$employeeId)
                    ->orWhereJsonContains('site_heads', $employeeId)
                    ->orWhereJsonContains('site_heads', (int)$employeeId);
                });
            }
            
            // Also include projects where assigned (if needed)
            if ($employeeId) {
                $projectsQuery->orWhere(function($q) use ($employeeId) {
                    $q->whereJsonContains('assigned_data', ['employee_ids' => [(string)$employeeId]])
                    ->orWhereJsonContains('assigned_data', ['employee_ids' => [$employeeId]])
                    ->orWhereJsonContains('assigned_data', ['employee_ids' => (string)$employeeId])
                    ->orWhereJsonContains('assigned_data', ['employee_ids' => $employeeId]);
                });
            }
            
            $projectsList = $projectsQuery->pluck('project_name', 'id')->prepend(__('Select Project'), '');
        } else {
            $projectsList = Project::pluck('project_name', 'id')->prepend(__('Select Project'), '');
        }

        if ($request->ajax()) {
            return view('timesheet.partials.table', compact('timeSheets'));
        }

        return view('timeSheet.index', compact('timeSheets', 'employeesList', 'projectsList'));
    }

    public function create()
    {
        if (Auth::user()->can('Create TimeSheet')) {
            // Exclude terminated employees from dropdowns
            $employees = Employee::query()
                ->whereNotIn('id', function ($q) {
                    $this->terminatedEmployeeSubquery($q);
                })
                ->pluck('name', 'user_id')
                ->prepend('Select Employee', '');

            $allEmployees = Employee::query()
                ->whereNotIn('id', function ($q) {
                    $this->terminatedEmployeeSubquery($q);
                })
                ->pluck('name', 'id')
                ->prepend('Select Presale Employee', '');
            
            // Initialize projects array
            $projects = [];
            
            if (Auth::user()->type == 'employee') {
                // Get current employee ID (from employees table id column)
                $employeeId = Auth::user()->employee->id; // Assuming you have employee relationship
                
                // Get all projects and filter those assigned to this employee
                $allProjects = Project::all();
                
                foreach ($allProjects as $project) {
                    if (empty($project->assigned_data)) {
                        continue;
                    }
                    
                    // Check if this employee is assigned to the project
                    $isAssigned = false;
                    $assignedData = $project->assigned_data; // Already an array due to JSON casting
                    
                    foreach ($assignedData as $assignment) {
                        if (isset($assignment['employee_ids']) && 
                            is_array($assignment['employee_ids']) &&
                            in_array($employeeId, $assignment['employee_ids'])) {
                            $isAssigned = true;
                            break;
                        }
                    }
                    
                    if ($isAssigned) {
                        $projects[$project->id] = $project->project_name;
                    }
                }
            } else {
                // For admins/managers, get all projects
                $projects = Project::pluck('project_name', 'id');
            }

            return view('timeSheet.create', compact('employees', 'projects', 'allEmployees'));
        }

        return redirect()->back()->with('error', 'Permission denied.');
    }

    public function store(Request $request)
    {
        \Log::info('Store method called');
        \Log::info($request->all());

        if (!Auth::user()->can('Create TimeSheet')) {
            return redirect()->back()->with('error', 'Permission denied.');
        }

        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'full_name' => 'required|string|max:255',
            'mobile_no' => 'required|string|max:20',
            'email_id' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'recommended_by' => 'nullable|string|in:CP,Digital,Hoarding,WayBoard,Walking,LeafLet,Expo,Refrence,Others',
            'cp_data' => 'required_if:recommended_by,CP|nullable|string|max:255',
            'refrence_data' => 'required_if:recommended_by,Refrence|nullable|string|max:255',
            'other_data' => 'required_if:recommended_by,Others|nullable|string|max:255',
            'feedback_information' => 'nullable|array',
            'feedback_followup_date' => 'nullable|array|size:'.count($request->feedback_information ?? []),
            'feedback_followup_date.*' => 'nullable|date',
            'presale_employee_id' => 'nullable|exists:employees,id',
            'follow_up_date' => 'nullable|date|after_or_equal:today',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $timeSheetData = $request->only([
                'employee_id', 'project_id', 'unit_id', 'date', 
                'full_name', 'mobile_no', 'email_id', 'address',
                'recommended_by', 'refrence_data', 'other_data', 'cp_data',
                'primary_reason', 'square_feet_range', 'price_range',
                'client_status', 'executive_remark', 'follow_up_date', 'presale_employee_id', 'assigned_to',
            ]);

            // Set original creator
            $timeSheetData['employee_id'] = Auth::id();
            
            // If assigned_to is empty, set it to null (not the current user)
            if (empty($timeSheetData['assigned_to'])) {
                $timeSheetData['assigned_to'] = null;
            }

            // Process feedback data
            $feedbackArray = [];
            if ($request->has('feedback_information')) {
                foreach ($request->feedback_information as $index => $feedback) {
                    if (!empty(trim($feedback))) {
                        $feedbackArray[] = [
                            'description' => trim($feedback),
                            'followup_date' => $request->feedback_followup_date[$index] ?? null,
                            'created_at' => now()->toDateTimeString(),
                            'added_by' => Auth::user()->employee->name ?? Auth::user()->name
                        ];
                    }
                }
                
                $timeSheetData['feedback_information'] = !empty($feedbackArray) ? json_encode($feedbackArray) : null;
            }

            $timeSheet = TimeSheet::create($timeSheetData);

            // Send email immediately if follow-up date is today
            $emailSent = false;
            if (!empty($timeSheetData['follow_up_date'])) {
                try {
                    $followUpDate = Carbon::parse($timeSheetData['follow_up_date'])->startOfDay();
                    $today = Carbon::today()->startOfDay();
                    
                    Log::info("Checking follow-up date for immediate email (create)", [
                        'timesheet_id' => $timeSheet->id,
                        'follow_up_date_raw' => $timeSheetData['follow_up_date'],
                        'follow_up_date_parsed' => $followUpDate->toDateString(),
                        'today' => $today->toDateString(),
                        'comparison' => $followUpDate->format('Y-m-d') . ' vs ' . $today->format('Y-m-d')
                    ]);
                    
                    // Compare dates as strings to avoid timezone issues
                    if ($followUpDate->format('Y-m-d') === $today->format('Y-m-d')) {
                        Log::info("Follow-up date is today, sending email immediately (create)");
                        $emailSent = $this->sendFollowUpEmail($timeSheet);
                        Log::info("Email send result (create): " . ($emailSent ? 'SUCCESS' : 'FAILED'));
                    } else {
                        Log::info("Follow-up date is NOT today, skipping immediate email (create)");
                    }
                } catch (\Exception $e) {
                    Log::error("Error checking follow-up date (create)", [
                        'error' => $e->getMessage(),
                        'follow_up_date' => $timeSheetData['follow_up_date'] ?? 'null'
                    ]);
                }
            }

            $message = __('Timesheet created successfully');
            if ($emailSent) {
                $message .= ' ' . __('Follow-up reminder email has been sent.');
            }

            return redirect()->route('timesheet.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            \Log::error('Timesheet creation failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error creating timesheet: ' . $e->getMessage())
                ->withInput();
        }
    }


    public function edit(TimeSheet $timeSheet)
    {
        if (Auth::user()->can('Edit TimeSheet')) {
            // Exclude terminated employees from dropdowns
            $employees = Employee::query()
                ->whereNotIn('id', function ($q) {
                    $this->terminatedEmployeeSubquery($q);
                })
                ->pluck('name', 'user_id')
                ->toArray();

            $allEmployees = Employee::query()
                ->whereNotIn('id', function ($q) {
                    $this->terminatedEmployeeSubquery($q);
                })
                ->pluck('name', 'id')
                ->toArray();
            
            // Initialize projects array
            $projects = [];
            
            if (Auth::user()->type == 'employee') {
                // Get current employee ID (from employees table id column)
                $employeeId = Auth::user()->employee->id; // Assuming you have employee relationship
                
                // Get all projects and filter those assigned to this employee
                $allProjects = Project::all();
                
                foreach ($allProjects as $project) {
                    if (empty($project->assigned_data)) {
                        continue;
                    }
                    
                    // Check if this employee is assigned to the project
                    $isAssigned = false;
                    $assignedData = $project->assigned_data; // Already an array due to JSON casting
                    
                    foreach ($assignedData as $assignment) {
                        if (isset($assignment['employee_ids']) && 
                            is_array($assignment['employee_ids']) &&
                            in_array($employeeId, $assignment['employee_ids'])) {
                            $isAssigned = true;
                            break;
                        }
                    }
                    
                    if ($isAssigned) {
                        $projects[$project->id] = $project->project_name;
                    }
                }
            } else {
                // For admins/managers, get all projects
                $projects = Project::pluck('project_name', 'id');
            }
            
            // Decode the feedback information
            $feedbacks = [];
            if (!empty($timeSheet->feedback_information)) {
                $feedbacks = json_decode($timeSheet->feedback_information, true);
            }
            
            return view('timeSheet.edit', compact(
                'timeSheet',
                'employees',
                'projects',
                'allEmployees',
                'feedbacks' // Pass the decoded feedbacks to the view
            ));
        }
        return redirect()->back()->with('error', 'Permission denied.');
    }

    public function update(Request $request, $id)
    {
        \Log::info('Update request data:', $request->all());

        if (!Auth::user()->can('Edit TimeSheet')) {
            return redirect()->back()->with('error', 'Permission denied.');
        }

        $timeSheet = TimeSheet::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            // 'employee_id' => 'required|exists:users,id',
            'project_id' => 'required|exists:projects,id',
            'date' => 'required|date',
            'full_name' => 'required|string|max:255',
            'mobile_no' => 'string|max:20',
            'email_id' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'recommended_by' => 'nullable|string|in:CP,Digital,Hoarding,WayBoard,Walking,LeafLet,Expo,Refrence,Others',
            'cp_data' => 'required_if:recommended_by,CP|nullable|string|max:255',
            'refrence_data' => 'required_if:recommended_by,Refrence|nullable|string|max:255',
            'other_data' => 'required_if:recommended_by,Others|nullable|string|max:255',
            'primary_reason' => 'nullable|string|in:Investment,Construction',
            'square_feet_range' => 'nullable|string|max:50',
            'price_range' => 'nullable|string|in:10 To 15 lakh,15 To 20 lakh,20 To 30 lakh,30 To 40 lakh,40 To 50 lakh,50 Lakh Above',
            'client_status' => 'nullable|string|in:Intrested,Not-Intrested,Call-Back,Hold,Booked',
            'executive_remark' => 'nullable|string|max:1000',
            'follow_up_date' => 'nullable|date',
            'presale_employee_id' => 'nullable|exists:employees,id',
            'feedback_information' => 'nullable|array',
            'feedback_information.*' => 'nullable|string|max:1000',
            'feedback_followup_date' => 'nullable|array',
            'feedback_followup_date.*' => 'nullable|date',
        ], [
            // 'employee_id.exists' => 'The selected employee does not exist.',
            'project_id.exists' => 'The selected project does not exist.',
            'presale_employee_id.exists' => 'The selected presale employee does not exist.',
            'required_if' => 'The :attribute field is required when :other is :value.',
        ]);

        if ($validator->fails()) {
            \Log::error('Validation failed:', $validator->errors()->all());
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Validation failed. Please check your inputs: ' . implode(' ', $validator->errors()->all()));
        }

        try {
            $timeSheetData = $request->only([
                'employee_id', 'project_id', 'unit_id', 'date', 
                'full_name', 'mobile_no', 'email_id', 'address',
                'recommended_by', 'primary_reason', 'square_feet_range',
                'price_range', 'client_status', 'executive_remark', 'follow_up_date',
                'presale_employee_id',  'assigned_to',
            ]);



            // Handle client source specific data
            $timeSheetData['cp_data'] = $request->recommended_by === 'CP' ? $request->cp_data : null;
            $timeSheetData['refrence_data'] = $request->recommended_by === 'Refrence' ? $request->refrence_data : null;
            $timeSheetData['other_data'] = $request->recommended_by === 'Others' ? $request->other_data : null;

            // Process feedback data
            $feedbackArray = [];
            // In your update method:
            if ($request->has('feedback_information')) {
                $feedbackArray = [];
                
                // Get existing feedbacks to preserve original creators
                $existingFeedbacks = [];
                if (!empty($timeSheet->feedback_information)) {
                    $existingFeedbacks = json_decode($timeSheet->feedback_information, true);
                }

                foreach ($request->feedback_information as $index => $feedback) {
                    if (!empty(trim($feedback))) {
                        // Check if this is an existing feedback being edited
                        if (isset($existingFeedbacks[$index])) {
                            // Preserve original creator info, add updated info
                            $feedbackArray[] = [
                                'description' => trim($feedback),
                                'followup_date' => $request->feedback_followup_date[$index] ?? null,
                                'created_at' => $existingFeedbacks[$index]['created_at'],
                                'added_by' => $existingFeedbacks[$index]['added_by'],
                                'updated_at' => now()->toDateTimeString(),
                                'updated_by' => Auth::user()->employee->name ?? Auth::user()->name
                            ];
                        } else {
                            // This is a new feedback
                            $feedbackArray[] = [
                                'description' => trim($feedback),
                                'followup_date' => $request->feedback_followup_date[$index] ?? null,
                                'created_at' => now()->toDateTimeString(),
                                'added_by' => Auth::user()->employee->name ?? Auth::user()->name
                            ];
                        }
                    }
                }
                
                $timeSheetData['feedback_information'] = !empty($feedbackArray) ? json_encode($feedbackArray) : null;
            }

            $timeSheet->update($timeSheetData);
            
            // Refresh the model to get updated data
            $timeSheet->refresh();

            // Send email immediately if follow-up date is today (and it was just set/changed)
            $emailSent = false;
            if (!empty($timeSheetData['follow_up_date'])) {
                try {
                    $followUpDate = Carbon::parse($timeSheetData['follow_up_date'])->startOfDay();
                    $today = Carbon::today()->startOfDay();
                    
                    Log::info("Checking follow-up date for immediate email (update)", [
                        'timesheet_id' => $timeSheet->id,
                        'follow_up_date_raw' => $timeSheetData['follow_up_date'],
                        'follow_up_date_parsed' => $followUpDate->toDateString(),
                        'today' => $today->toDateString(),
                        'is_today' => $followUpDate->equalTo($today),
                        'comparison' => $followUpDate->format('Y-m-d') . ' vs ' . $today->format('Y-m-d')
                    ]);
                    
                    // Compare dates as strings to avoid timezone issues
                    if ($followUpDate->format('Y-m-d') === $today->format('Y-m-d')) {
                        Log::info("Follow-up date is today, sending email immediately (update)");
                        $emailSent = $this->sendFollowUpEmail($timeSheet);
                        Log::info("Email send result: " . ($emailSent ? 'SUCCESS' : 'FAILED'));
                    } else {
                        Log::info("Follow-up date is NOT today, skipping immediate email");
                    }
                } catch (\Exception $e) {
                    Log::error("Error checking follow-up date", [
                        'error' => $e->getMessage(),
                        'follow_up_date' => $timeSheetData['follow_up_date'] ?? 'null'
                    ]);
                }
            } else {
                Log::info("No follow-up date set, skipping email");
            }

            $message = __('Timesheet updated successfully');
            if ($emailSent) {
                $message .= ' ' . __('Follow-up reminder email has been sent.');
            }

            return redirect()->route('timesheet.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            \Log::error('Timesheet update failed: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return redirect()->back()
                ->with('error', 'Error updating timesheet: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy($id)
    {
        if (Auth::user()->can('Delete TimeSheet')) {
            $timeSheet = TimeSheet::findOrFail($id);
            $timeSheet->delete();
            return redirect()->route('timesheet.index')->with('success', 'Timesheet deleted successfully!');
        }

        return redirect()->back()->with('error', 'Permission denied.');
    }

    public function show(TimeSheet $timeSheet)
    {
        try {
            // Load relationships with error handling
            $timeSheet->load([
                'employee' => function($query) {
                    $query->select('id', 'name');
                },
                'project' => function($query) {
                    $query->select('id', 'project_name');
                },
                'presaleEmployee' => function($query) {
                    $query->select('id', 'name');
                }
            ]);
            
            // Decode feedback information
            $feedbacks = [];
            if (!empty($timeSheet->feedback_information)) {
                $feedbacks = json_decode($timeSheet->feedback_information, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $feedbacks = [];
                }
            }
            
            return view('timeSheet.show', compact('timeSheet', 'feedbacks'));
            
        } catch (\Exception $e) {
            \Log::error("Error showing timesheet: " . $e->getMessage());
            abort(500, 'Error loading timesheet details');
        }
    }

    public function export(Request $request)
    {
        // Check if we have filtered timesheets in session
        if (session()->has('export_timesheets')) {
            $timeSheets = session('export_timesheets');
            
            // Clear the session after use
            session()->forget('export_timesheets');
        } else {
            // Fallback - get all visible timesheets if no filters were applied
            $query = TimeSheet::with(['employee', 'project']);

            if (Auth::user()->type == 'employee') {
                $userId = Auth::id();
                $employeeId = Auth::user()->employee->id;
                
                $query->where(function($q) use ($userId, $employeeId) {
                    $q->where('employee_id', $userId)
                    ->orWhere('assigned_to', $userId)
                    ->orWhereHas('project', function($projectQuery) use ($userId) {
                        $projectQuery->whereJsonContains('site_heads', (string)$userId)
                                    ->orWhereJsonContains('site_heads', $userId);
                    })
                    ->orWhereHas('project', function($projectQuery) use ($employeeId) {
                        $projectQuery->whereJsonContains('assigned_data', 
                            ['employee_ids' => [(string)$employeeId]]
                        )->orWhereJsonContains('assigned_data', 
                            ['employee_ids' => $employeeId]
                        );
                    });
                });
            }

            $timeSheets = $query->latest()->get();
        }

        // Generate file name with timestamp
        $fileName = 'enquiries_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        return Excel::download(new TimeSheetExport($timeSheets), $fileName);
    }
        
    public function getEmployeeProjects($userId)
    {
        try {
            // Get the employee record from employees table where user_id matches
            $employee = Employee::where('user_id', $userId)->first();
            
            if (!$employee) {
                return response()->json(['projects' => []]);
            }

            // Get all projects
            $allProjects = Project::all();
            $assignedProjects = [];

            foreach ($allProjects as $project) {
                if (empty($project->assigned_data)) {
                    continue;
                }

                // Check if this employee is assigned to the project
                $isAssigned = false;
                $assignedData = $project->assigned_data;

                foreach ($assignedData as $assignment) {
                    if (isset($assignment['employee_ids']) && 
                        is_array($assignment['employee_ids']) &&
                        in_array($employee->id, $assignment['employee_ids'])) {
                        $isAssigned = true;
                        break;
                    }
                }

                if ($isAssigned) {
                    $assignedProjects[] = [
                        'id' => $project->id,
                        'project_name' => $project->project_name
                    ];
                }
            }

            return response()->json(['projects' => $assignedProjects]);
        } catch (\Exception $e) {
            \Log::error("Error getting employee projects: " . $e->getMessage());
            return response()->json(['projects' => []], 500);
        }
    }

    public function getProjectEmployees($projectId)
    {
        try {
            \Log::info("Fetching employees for project: $projectId");
            
            $project = Project::find($projectId);
            
            if (!$project) {
                \Log::error("Project not found: $projectId");
                return response()->json(['error' => 'Project not found'], 404);
            }

            \Log::debug('Project assigned_data:', [$project->assigned_data]);

            $employeeIds = [];
            
            // Safely extract employee IDs
            if ($project->assigned_data && is_array($project->assigned_data)) {
                foreach ($project->assigned_data as $assignment) {
                    if (isset($assignment['employee_ids']) && is_array($assignment['employee_ids'])) {
                        $employeeIds = array_merge($employeeIds, $assignment['employee_ids']);
                    }
                }
            }

            \Log::debug('Extracted employee IDs:', $employeeIds);

            // Convert all IDs to strings for comparison (since JSON might store them as strings)
            $employeeIds = array_map('strval', array_unique($employeeIds));

            // Get employees with their user accounts
            $employees = Employee::whereIn('id', $employeeIds)
                ->whereNotIn('id', function ($q) {
                    $this->terminatedEmployeeSubquery($q);
                })
                ->with('user:id,name')
                ->get()
                ->map(function ($employee) {
                    return [
                        'id' => $employee->user->id,
                        'name' => $employee->user->name
                    ];
                });

            \Log::debug('Found employees:', [$employees]);

            return response()->json([
                'employees' => $employees,
                'project' => $project->project_name
            ]);
            
        } catch (\Exception $e) {
            \Log::error("Error in getProjectEmployees: " . $e->getMessage());
            return response()->json([
                'error' => 'Error fetching employees',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send follow-up reminder email immediately
     * 
     * @param TimeSheet $timeSheet
     * @return bool
     */
    private function sendFollowUpEmail(TimeSheet $timeSheet)
    {
        try {
            Log::info("Attempting to send follow-up email", [
                'timesheet_id' => $timeSheet->id,
                'client_name' => $timeSheet->full_name,
                'assigned_to' => $timeSheet->assigned_to,
                'employee_id' => $timeSheet->employee_id
            ]);
            
            // Refresh to get latest data
            $timeSheet->refresh();
            
            // Load relationships - make sure project is loaded
            $timeSheet->load(['employee', 'assignedEmployee', 'project']);
            
            // Ensure feedback_information is accessible as array
            if (!empty($timeSheet->feedback_information) && is_string($timeSheet->feedback_information)) {
                // If it's still a string, it means the cast didn't work, so decode it manually
                $decoded = json_decode($timeSheet->feedback_information, true);
                $timeSheet->setAttribute('feedback_information', $decoded);
            }
            
            // Determine which employee to send email to
            // Priority: assigned_to employee > original employee (employee_id)
            $employeeToNotify = null;
            $employeeEmail = null;
            $userForEmail = null;
            
            // Try to get employee from assigned_to (if exists)
            if ($timeSheet->assigned_to) {
                $assignedUser = User::find($timeSheet->assigned_to);
                Log::info("Checking assigned user", [
                    'assigned_to' => $timeSheet->assigned_to,
                    'user_found' => $assignedUser ? 'yes' : 'no'
                ]);
                
                if ($assignedUser) {
                    $employeeToNotify = Employee::where('user_id', $assignedUser->id)->first();
                    $userForEmail = $assignedUser;
                    Log::info("Assigned employee found", [
                        'employee_id' => $employeeToNotify ? $employeeToNotify->id : null,
                        'email' => $employeeToNotify ? ($employeeToNotify->email ?? 'null') : null
                    ]);
                }
            }
            
            // If no assigned employee, use the original creator
            if (!$employeeToNotify && $timeSheet->employee_id) {
                $originalUser = User::find($timeSheet->employee_id);
                Log::info("Checking original user", [
                    'employee_id' => $timeSheet->employee_id,
                    'user_found' => $originalUser ? 'yes' : 'no'
                ]);
                
                if ($originalUser) {
                    $employeeToNotify = Employee::where('user_id', $originalUser->id)->first();
                    $userForEmail = $originalUser;
                    Log::info("Original employee found", [
                        'employee_id' => $employeeToNotify ? $employeeToNotify->id : null,
                        'email' => $employeeToNotify ? ($employeeToNotify->email ?? 'null') : null
                    ]);
                }
            }
            
            // Get employee email - try employee email first, then user email as fallback
            if ($employeeToNotify) {
                $employeeEmail = $employeeToNotify->email;
                
                // If employee email is empty, try to get from user
                if (empty($employeeEmail) && $userForEmail && !empty($userForEmail->email)) {
                    $employeeEmail = $userForEmail->email;
                    Log::info("Using user email as fallback", ['email' => $employeeEmail]);
                }
            } elseif ($userForEmail && !empty($userForEmail->email)) {
                // If no employee record but we have a user, use user email
                $employeeEmail = $userForEmail->email;
                Log::info("Using user email directly (no employee record)", ['email' => $employeeEmail]);
            }
            
            if (empty($employeeEmail)) {
                Log::warning("Cannot send follow-up email: No valid employee email found", [
                    'timesheet_id' => $timeSheet->id,
                    'assigned_to' => $timeSheet->assigned_to,
                    'employee_id' => $timeSheet->employee_id,
                    'employee_found' => $employeeToNotify ? 'yes' : 'no',
                    'employee_email' => $employeeToNotify ? ($employeeToNotify->email ?? 'empty') : 'no employee'
                ]);
                return false;
            }
            
            // Get the last remark
            $lastRemark = $this->getLastRemark($timeSheet);
            
            // Configure mail settings from database (IMPORTANT!)
            // Get the company/user ID - use current logged in user's creator ID or default to 1
            $companyId = Auth::check() ? Auth::user()->creatorId() : 1;
            $mailSettings = Utility::getSMTPDetails($companyId);
            
            // Normalize mail driver to lowercase (Laravel expects 'smtp' not 'SMTP')
            if (!empty($mailSettings['mail_driver'])) {
                $mailDriver = strtolower(trim($mailSettings['mail_driver']));
                // Ensure it's a valid mailer name
                if ($mailDriver === 'smtp' || $mailDriver === 'mail' || $mailDriver === 'sendmail') {
                    config(['mail.default' => $mailDriver]);
                } else {
                    // Default to smtp if invalid value
                    config(['mail.default' => 'smtp']);
                }
            } else {
                // Default to smtp if not set
                config(['mail.default' => 'smtp']);
            }
            
            Log::info("Sending email now", [
                'to' => $employeeEmail,
                'client_name' => $timeSheet->full_name,
                'company_id' => $companyId,
                'mail_driver' => config('mail.default'),
                'follow_up_date' => $timeSheet->follow_up_date,
                'last_remark' => substr($lastRemark, 0, 50) . '...'
            ]);
            
            // Send email
            Mail::to($employeeEmail)->send(new FollowUpReminder($timeSheet, $lastRemark));
            
            Log::info("Follow-up reminder sent immediately - SUCCESS", [
                'timesheet_id' => $timeSheet->id,
                'client_name' => $timeSheet->full_name,
                'employee_email' => $employeeEmail,
                'follow_up_date' => $timeSheet->follow_up_date
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error("Error sending immediate follow-up reminder", [
                'timesheet_id' => $timeSheet->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            // Don't throw exception - we don't want to break the save/update process
            return false;
        }
    }
    
    /**
     * Get the last remark for a timesheet
     * Priority: Last feedback > Executive remark
     * 
     * @param TimeSheet $timeSheet
     * @return string
     */
    private function getLastRemark(TimeSheet $timeSheet)
    {
        // Check if there are feedbacks
        if (!empty($timeSheet->feedback_information)) {
            // feedback_information is already cast as array in the model, but check to be safe
            $feedbacks = [];
            if (is_array($timeSheet->feedback_information)) {
                $feedbacks = $timeSheet->feedback_information;
            } elseif (is_string($timeSheet->feedback_information)) {
                $feedbacks = json_decode($timeSheet->feedback_information, true);
            }
            
            if (is_array($feedbacks) && !empty($feedbacks)) {
                // Get the last feedback (most recent)
                $lastFeedback = end($feedbacks);
                if (isset($lastFeedback['description']) && !empty(trim($lastFeedback['description']))) {
                    return trim($lastFeedback['description']);
                }
            }
        }
        
        // Fallback to executive remark
        if (!empty($timeSheet->executive_remark)) {
            return trim($timeSheet->executive_remark);
        }
        
        return 'No remarks available.';
    }

}




