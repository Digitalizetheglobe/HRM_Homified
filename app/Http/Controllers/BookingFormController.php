<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Employee;
use App\Models\BookingForm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\BookingsExport;
use App\Models\User;
use App\Models\Unit;
use Illuminate\Validation\Rule;
use App\Models\TimeSheet;
use Illuminate\Support\Facades\Crypt;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use App\Notifications\BookingCreatedNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use App\Mail\BookingConfirmation;

class BookingFormController extends Controller
{
    /**
     * Booking employee dropdown options (User IDs) excluding terminated employees.
     *
     * Note: termination is tracked on employees table via Termination records.
     */
    private function bookingAssignableEmployees(): array
    {
        $companyId = Auth::user()->creatorId();

        $employees = Employee::query()
            ->with('user')
            ->where('created_by', $companyId)
            ->whereHas('user', function ($q) {
                $q->where('type', 'employee');
            })
            ->notTerminated()
            ->get();

        // Map to [user_id => user.name]
        return $employees
            ->filter(fn ($e) => !empty($e->user_id))
            ->mapWithKeys(function ($e) {
                $label = $e->user->name ?? $e->name ?? ('Employee #' . $e->user_id);
                return [$e->user_id => $label];
            })
            ->toArray();
    }

    /**
     * Throws if the given user_id belongs to a terminated employee.
     */
    private function ensureNotTerminatedEmployeeUserId($userId): void
    {
        if (empty($userId)) {
            throw new \Exception('Employee ID is required.');
        }

        $employee = Employee::where('user_id', $userId)->first();
        if (!$employee) {
            throw new \Exception('Selected employee not found.');
        }

        if ($employee->activeTermination()->exists()) {
            throw new \Exception('Selected employee is terminated and cannot be used for booking.');
        }
    }

    public function create()
    {
        $canCreate = Auth::user()->can('Create TimeSheet') ||
                     $this->isFinanceAccountsUser(Auth::user());

        if (!$canCreate) {
            return redirect()->back()->with('error', 'Permission denied.');
        }

        $employees = $this->bookingAssignableEmployees();
        $projects = Project::pluck('project_name', 'id');
        $bookingForm = new BookingForm();

        return view('booking.create', compact('employees', 'projects', 'bookingForm'));
    }

    public function edit($id)
    {
        $canEdit = Auth::user()->can('Edit TimeSheet') ||
                   $this->isFinanceAccountsUser(Auth::user());

        if (!$canEdit) {
            return redirect()->back()->with('error', 'Permission denied.');
        }

        $bookingForm = BookingForm::findOrFail($id);

        $employees = $this->bookingAssignableEmployees();
        $projects = Project::pluck('project_name', 'id');
        $projectTypes = Project::pluck('project_type', 'id');
        $projectType = optional(Project::find($bookingForm->project_id))->project_type;

        // Enquiries: include current enquiry + any unbooked enquiries for the project
        $enquiries = collect();
        if (!empty($bookingForm->project_id)) {
            $enquiries = TimeSheet::where('project_id', $bookingForm->project_id)
                ->where(function ($q) use ($bookingForm) {
                    $q->where('is_booked', 0)
                      ->orWhere('id', $bookingForm->enquiry_id);
                })
                ->get(['id', 'full_name', 'mobile_no']);
        }

        // Units: include current unit + all not-booked by other bookings in this project
        $units = collect();
        if (!empty($bookingForm->project_id)) {
            $bookedUnitIds = BookingForm::where('project_id', $bookingForm->project_id)
                ->where('id', '!=', $bookingForm->id)
                ->pluck('unit_id')
                ->toArray();

            $units = Unit::where('project_id', $bookingForm->project_id)
                ->where(function ($q) use ($bookedUnitIds, $bookingForm) {
                    $q->whereNotIn('id', $bookedUnitIds)
                      ->orWhere('id', $bookingForm->unit_id);
                })
                ->get(['id', 'unit_name', 'unit_size']);
        }

        return view('booking.edit', compact(
            'bookingForm',
            'employees',
            'projects',
            'projectTypes',
            'projectType',
            'enquiries',
            'units'
        ));
    }
    public function index(Request $request)
    {
        if (Auth::user()->can('Manage TimeSheet')) {
            $query = BookingForm::with(['employee', 'project', 'unit', 'timesheet']);

            // For employees, show their own bookings AND site head projects
            // Finance & Accounts can see ALL bookings in addition to existing rules
            if (Auth::user()->type == 'employee') {
                $userId = Auth::id();
                $employeeId = Auth::user()->employee->id ?? null;
                
                // Check if user is in Finance & Accounts department
                $isFinanceAccounts = $this->isFinanceAccountsUser(Auth::user());
                
                // If NOT Finance & Accounts, apply the normal filters
                if (!$isFinanceAccounts) {
                    $query->where(function($q) use ($userId, $employeeId) {
                        // Bookings where current user is the employee
                        $q->where('employee_id', $userId)
                        
                        // OR bookings for projects where user is site head (read-only access)
                        ->orWhereHas('project', function($projectQuery) use ($employeeId) {
                            $projectQuery->where(function($pq) use ($employeeId) {
                                $pq->whereJsonContains('site_heads', (string)$employeeId)
                                    ->orWhereJsonContains('site_heads', $employeeId)
                                    ->orWhereJsonContains('site_heads', (int)$employeeId);
                            });
                        });
                    });
                }
                // If user IS Finance & Accounts, they can see ALL bookings (no additional filtering needed)
            }

            // Date filters
            if (!empty($request->start_date)) {
                $query->whereDate('booking_date', '>=', $request->start_date);
            }

            if (!empty($request->end_date)) {
                $query->whereDate('booking_date', '<=', $request->end_date);
            }

            // Filter by project if selected
            if (!empty($request->project)) {
                $query->where('project_id', $request->project);
            }

            // Combined status filter
            if (!empty($request->status)) {
                switch ($request->status) {
                    case 'active':
                        $query->where('is_cancelled', 0)->where('remaining', '>', 0);
                        break;
                    case 'completed':
                        $query->where('is_cancelled', 0)->where('remaining', '<=', 0);
                        break;
                    case 'cancelled':
                        $query->where('is_cancelled', 1);
                        break;
                    case 'agreement_done':
                        $query->where('agreement', 'done');
                        break;
                }
            }

            $bookings = $query->get();

            // Load projects for filter dropdown
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
                
                $projects = $projectsQuery->pluck('project_name', 'id')->prepend(__('Select Project'), '');
            } else {
                // For admins/managers, get all projects
                $projects = Project::pluck('project_name', 'id');
            }

            // Determine which view to use based on the route
            $view = $request->route()->getName() == 'booking.all' ? 'booking.allbooking' : 'booking.index';

            return view($view, compact('bookings', 'projects'));
        }

        return redirect()->back()->with('error', 'Permission denied.');
    }

    private function isSiteHead($userId, $project)
    {
        if (!$project || empty($project->site_heads)) {
            return false;
        }
        
        $siteHeadIds = $project->site_heads;
        
        // Handle different formats of site_heads data
        if (is_array($siteHeadIds)) {
            return in_array((string)$userId, $siteHeadIds);
        } elseif (is_string($siteHeadIds)) {
            // Handle JSON string that might need decoding
            try {
                $decoded = json_decode($siteHeadIds, true);
                if (is_array($decoded)) {
                    return in_array((string)$userId, $decoded);
                }
            } catch (\Exception $e) {
                // If it's a simple string, compare directly
                return $siteHeadIds == (string)$userId;
            }
        }
        
        return false;
    }

    // Helper method to check if user is in Finance & Accounts department
    private function isFinanceAccountsUser($user)
    {
        // Adjust this based on how you store department information
        // Example 1: If department is stored in users table
        if (isset($user->department) && $user->department == 'Finance & Accounts') {
            return true;
        }
        
        // Example 2: If using roles
        if ($user->hasRole('Finance & Accounts')) {
            return true;
        }
        
        // Example 3: If using a specific user type
        if ($user->type == 'finance_accounts') {
            return true;
        }
        
        // Example 4: Check by email pattern (adjust as needed)
        if (strpos($user->email, 'finance@') !== false || 
            strpos($user->email, 'accounts@') !== false) {
            return true;
        }
        
        return false;
    }
    
    public function getEnquiriesByProject($projectId)
    {
        try {
            \Log::info("Fetching enquiries for project ID: $projectId");

            // Check if project exists
            $project = Project::find($projectId);
            if (!$project) {
                \Log::warning("Project not found: $projectId");
                return response()->json([
                    'enquiries' => [],
                    'units' => [],
                    'error' => 'Project not found'
                ], 404);
            }

            // Get total enquiries count for debugging
            $totalEnquiries = TimeSheet::where('project_id', $projectId)->count();
            $bookedEnquiries = TimeSheet::where('project_id', $projectId)
                                        ->where('is_booked', 1)
                                        ->count();
            
            \Log::info("Project: {$project->project_name}, Total enquiries: $totalEnquiries, Booked: $bookedEnquiries");

            // Get unbooked enquiries
            $enquiries = TimeSheet::where('project_id', $projectId)
                                ->where('is_booked', 0) // Only unbooked enquiries
                                ->get(['id', 'full_name', 'mobile_no', 'email_id', 'address']);

            \Log::info("Unbooked enquiries found: " . $enquiries->count());

            // Get all booked unit IDs for this project
            $bookedUnitIds = BookingForm::where('project_id', $projectId)
                ->pluck('unit_id')
                ->toArray();

            \Log::debug("Booked unit IDs:", $bookedUnitIds);

            // Get available units (not booked and is_approved)
            $units = Unit::where('project_id', $projectId)
                        ->where('is_approved', 1) // Only available units
                        ->whereNotIn('id', $bookedUnitIds) // Exclude already booked units
                        ->get(['id', 'unit_name', 'unit_size']);
            \Log::debug("Available units:", $units->toArray());
            

            return response()->json([
                'enquiries' => $enquiries,
                'units' => $units, // Return available units
                'total_enquiries' => $totalEnquiries,
                'booked_enquiries' => $bookedEnquiries,
                'unbooked_count' => $enquiries->count()
            ]);
        } catch (\Exception $e) {
            \Log::error("Error fetching enquiries for project $projectId: " . $e->getMessage());
            return response()->json([
                'enquiries' => [],
                'units' => [],
                'error' => 'Error fetching enquiries: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function addBooking(Request $request)
    {
        $canCreate = Auth::user()->can('Create TimeSheet') || 
                     $this->isFinanceAccountsUser(Auth::user());
        
        if ($canCreate) {
            try {
                // Initialize projects array based on user type
                $projects = [];
                
                if (Auth::user()->type == 'employee' && !$this->isFinanceAccountsUser(Auth::user())) {
                    $employee = Employee::where('user_id', Auth::user()->id)->first();
                    
                    if ($employee) {
                        $allProjects = Project::all();
                        
                        foreach ($allProjects as $project) {
                            if (empty($project->assigned_data)) continue;
                            
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
                                $projects[$project->id] = $project->project_name;
                            }
                        }
                    }
                } else {
                    // For admins/managers and Finance & Accounts, get all projects
                    $projects = Project::pluck('project_name', 'id');
                    $projectTypes = Project::pluck('project_type', 'id');
                }

                // Initialize other variables
                $enquiries = [];
                $selectedEnquiry = null;
                $units = collect();
                $bookingForm = new BookingForm();
                $projectType = null;

                // Employee dropdown (exclude terminated) for non-employee users
                $employees = $this->bookingAssignableEmployees();
                
                // If project is selected, load related data
                if ($request->has('project_id')) {
                    $project = Project::find($request->project_id);
                    $projectType = $project ? $project->project_type : null;
                    
                // Load enquiries for the selected project (only unbooked ones)
                        $enquiries = TimeSheet::where('project_id', $request->project_id)
                            ->where('is_booked', 0)
                            ->get(['id', 'full_name', 'mobile_no', 'email_id', 'address']);
                        
                        // Get all booked unit IDs for this project
                        $bookedUnitIds = BookingForm::where('project_id', $request->project_id)
                            ->pluck('unit_id')
                            ->toArray();

                        \Log::debug('Booked unit IDs: ' . json_encode($bookedUnitIds));

                        // Load available units for the selected project
                        $units = Unit::where('project_id', $request->project_id)
                            ->where('is_approved', 0) // Available units
                            ->whereNotIn('id', $bookedUnitIds)
                            ->get();

                        \Log::debug('Available units: ' . $units->pluck('id'));


                    // If enquiry is selected, pre-fill booking form
                    if ($request->has('enquiry_id')) {
                        $selectedEnquiry = TimeSheet::find($request->enquiry_id);
                        
                        if ($selectedEnquiry) {
                            // Pre-fill primary applicant details from enquiry
                            $bookingForm->primary_applicant_name = $selectedEnquiry->full_name;
                            $bookingForm->primary_applicant_contact_no = $selectedEnquiry->mobile_no;
                            $bookingForm->primary_applicant_email = $selectedEnquiry->email_id;
                            $bookingForm->primary_applicant_address = $selectedEnquiry->address;
                            
                            // If unit is already selected in the form, load its details
                            if ($request->has('unit_id')) {
                                $unit = Unit::find($request->unit_id);
                                if ($unit) {
                                    $bookingForm->unit_id = $unit->id;
                                    $bookingForm->unit_size = $unit->unit_size;
                                }
                            }
                        }
                    }
                }

                return view('booking.addbooking', [
                    'projects' => $projects,
                    'enquiries' => $enquiries,
                    'selectedEnquiry' => $selectedEnquiry,
                    'bookingForm' => $bookingForm,
                    'units' => $units,
                    'selectedProjectId' => $request->project_id,
                    'selectedUnitId' => $request->unit_id,
                    'projectType' => $projectType,
                    'employees' => $employees,
                ]);

            } catch (\Exception $e) {
                \Log::error('Error in BookingFormController@addBooking', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return redirect()->back()
                    ->with('error', 'Error loading booking form. Please try again.')
                    ->withInput();
            }
        }
        
        return redirect()->back()->with('error', 'Permission denied.');
    }
    
    public function store(Request $request)
    {
        $canCreate = Auth::user()->can('Create TimeSheet') || 
                     $this->isFinanceAccountsUser(Auth::user());
        
        if (!$canCreate) {
            return redirect()->back()->with('error', 'Permission denied.');
        }

        \Log::info('Booking form submission started', $request->except(['_token']));

        try {
            \DB::beginTransaction();

            // Validate required fields exist
            if (!$request->has('enquiry_id')) {
                throw new \Exception('Enquiry ID is required');
            }

            if (!$request->has('project_id')) {
                throw new \Exception('Project ID is required');
            }

            if (!$request->has('unit_id')) {
                throw new \Exception('Unit ID is required');
            }

            // Get employee_id based on user type
            $employeeId = null;
            if (Auth::user()->type == 'employee' && !$this->isFinanceAccountsUser(Auth::user())) {
                $employeeId = Auth::user()->id;
            } else {
                // For admin users and Finance & Accounts, you need to get employee_id from somewhere
                // Option 1: Get from request if available
                $employeeId = $request->employee_id;
                
                // Option 2: Get from the enquiry
                if (!$employeeId) {
                    $enquiry = TimeSheet::find($request->enquiry_id);
                    if ($enquiry && $enquiry->employee_id) {
                        $employeeId = $enquiry->employee_id;
                    }
                }
                
                // If still no employee_id, use a default or throw error
                if (!$employeeId) {
                    throw new \Exception('Employee ID is required for admin users');
                }
            }

            // Enforce: terminated employees cannot be assigned
            $this->ensureNotTerminatedEmployeeUserId($employeeId);

            // Get and validate the enquiry
            $enquiry = TimeSheet::find($request->enquiry_id);
            if (!$enquiry) {
                throw new \Exception('Enquiry not found');
            }

            if ($enquiry->is_booked) {
                throw new \Exception('This enquiry is already booked');
            }

            // Validate the project exists
            $project = Project::find($request->project_id);
            if (!$project) {
                throw new \Exception('Project not found');
            }

            // Validate the unit exists and belongs to the project
            $unit = Unit::where('id', $request->unit_id)
                    ->where('project_id', $request->project_id)
                    ->first();

            if (!$unit) {
                throw new \Exception('Unit not found or does not belong to the selected project');
            }

            // Check if unit is available (is_approved = 0 means available)
            if ($unit->is_approved != 0) {
                throw new \Exception('The selected unit is no longer available for booking. Unit status: ' . $unit->is_approved);
            }

            // Prepare payment data
            $paymentData = [];
            if ($request->has('payment_json')) {
                $paymentData = json_decode($request->payment_json, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception('Invalid payment JSON format');
                }
            }

            if (empty($paymentData)) {
                throw new \Exception('At least one payment is required');
            }

            // Validate total cost
            $totalCost = (float)($request->total_cost ?? 0);
            if ($totalCost <= 0) {
                throw new \Exception('Invalid total cost');
            }

            // Calculate financials
            $paidAmount = collect($paymentData)->sum('amount');
            $remaining = $totalCost - $paidAmount;

            if ($paidAmount <= 0) {
                throw new \Exception('Payment amount must be greater than zero');
            }

            // MARK UNIT AS BOOKED (is_approved = 1)
            $unit->is_approved = 1;
            if (!$unit->save()) {
                throw new \Exception('Failed to update unit status');
            }

            // Mark enquiry as booked
            $enquiry->is_booked = 1;
            if (!$enquiry->save()) {
                throw new \Exception('Failed to update enquiry status');
            }

            // Prepare booking data
            $bookingData = [
                'employee_id' => $employeeId,
                'project_id' => $project->id,
                'project_name' => $project->project_name,
                'unit_id' => $unit->id,
                'unit_name' => $unit->unit_name,
                'unit_size' => $unit->unit_size,
                'enquiry_id' => $enquiry->id,
                'booking_date' => $request->booking_date ?? now()->format('Y-m-d'),
                
                // Primary applicant
                'primary_applicant_name' => $request->primary_applicant_name,
                'primary_applicant_contact_no' => $request->primary_applicant_contact_no,
                'primary_applicant_email' => $request->primary_applicant_email,
                'primary_applicant_occupation' => $request->primary_applicant_occupation,
                'primary_applicant_company' => $request->primary_applicant_company,
                'primary_applicant_designation' => $request->primary_applicant_designation,
                'primary_applicant_birth_date' => $request->primary_applicant_birth_date,
                'primary_applicant_nationality' => $request->primary_applicant_nationality,
                'primary_applicant_pan_no' => $request->primary_applicant_pan_no,
                'primary_applicant_aadhar_no' => $request->primary_applicant_aadhar_no,
                
                // Secondary applicant
                'secondary_applicant_name' => $request->secondary_applicant_name,
                'secondary_applicant_contact_no' => $request->secondary_applicant_contact_no,
                'secondary_applicant_email' => $request->secondary_applicant_email,
                'secondary_applicant_occupation' => $request->secondary_applicant_occupation,
                'secondary_applicant_company' => $request->secondary_applicant_company,
                'secondary_applicant_designation' => $request->secondary_applicant_designation,
                'secondary_applicant_birth_date' => $request->secondary_applicant_birth_date,
                'secondary_applicant_nationality' => $request->secondary_applicant_nationality,
                'secondary_applicant_pan_no' => $request->secondary_applicant_pan_no,
                'secondary_applicant_aadhar_no' => $request->secondary_applicant_aadhar_no,
                
                // Financial details
                'plot_area' => $request->plot_area,
                'carpet_area' => $request->carpet_area,
                'built_up_area' => $request->built_up_area,
                'rate_per_sq_ft' => $request->rate_per_sq_ft,
                'basic_cost' => $request->basic_cost,
                'cost_infrastructure' => $request->cost_infrastructure,
                'gst' => $request->gst,
                'stamp_duty' => $request->stamp_duty,
                'registration' => $request->registration,
                'legal_charges' => $request->legal_charges,
                'other' => $request->other,
                'maintenance_cost' => $request->maintenance_cost,
                'total_cost' => $totalCost,
                'payment_data' => json_encode($paymentData),
                'remaining' => $remaining > 0 ? $remaining : null,
                'agreement_cost' => $request->agreement_cost ?? null,
            ];

            // Create booking
            $booking = BookingForm::create($bookingData);
            if (!$booking) {
                throw new \Exception('Failed to create booking record');
            }

            // Send notification to admins/managers
            $adminUsers = User::whereIn('type', ['company', 'hr', 'director'])
                ->where('created_by', Auth::user()->creatorId())
                ->get();

            $notificationData = [
                'booking_id' => $booking->id,
                'message' => 'New booking created: ' . $booking->primary_applicant_name . ' - ' . $booking->project_name . ' - ' . $booking->unit_name,
                'project_name' => $booking->project_name,
                'unit_name' => $booking->unit_name,
                'applicant_name' => $booking->primary_applicant_name,
                'url' => route('booking.show', $booking->id),
            ];

            foreach ($adminUsers as $adminUser) {
                $adminUser->notify(new BookingCreatedNotification($notificationData));
            }

            // Send booking confirmation email to client
            \Log::info('Checking if email should be sent', [
                'booking_id' => $booking->id,
                'email' => $booking->primary_applicant_email,
                'email_empty' => empty($booking->primary_applicant_email)
            ]);
            
            if (!empty($booking->primary_applicant_email)) {
                try {
                    \Log::info('Starting email sending process', [
                        'booking_id' => $booking->id,
                        'email' => $booking->primary_applicant_email
                    ]);
                    
                    // Refresh booking from database and load relationships
                    $booking->refresh();
                    $booking->load(['project', 'employee', 'unit']);
                    
                    // Get employee details (employee_id is user_id, so get employee from user_id)
                    $employee = null;
                    if ($booking->employee_id) {
                        $employee = Employee::where('user_id', $booking->employee_id)->first();
                        \Log::info('Employee found', [
                            'employee_id' => $booking->employee_id,
                            'employee' => $employee ? 'found' : 'not found'
                        ]);
                    }
                    
                    // Get project details
                    $project = $booking->project ?? Project::find($booking->project_id);
                    
                    if (!$project) {
                        throw new \Exception('Project not found for email');
                    }
                    
                    \Log::info('Project found', [
                        'project_id' => $project->id,
                        'project_name' => $project->project_name
                    ]);
                    
                    // Get first payment from payment_data
                    $firstPayment = [
                        'amount' => 0,
                        'mode' => 'N/A',
                        'date' => null,
                        'payment_detail' => null
                    ];
                    
                    if ($booking->payment_data) {
                        $paymentData = is_array($booking->payment_data) ? $booking->payment_data : json_decode($booking->payment_data, true);
                        if (is_array($paymentData) && count($paymentData) > 0) {
                            $firstPayment = $paymentData[0];
                        }
                    }
                    
                    \Log::info('Payment data extracted', [
                        'first_payment' => $firstPayment
                    ]);
                    
                    // Configure mail settings
                    $settings = \App\Models\Utility::getSMTPDetails(Auth::user()->creatorId());
                    \Log::info('SMTP settings retrieved', [
                        'settings_found' => !empty($settings),
                        'mail_driver' => $settings['mail_driver'] ?? 'not set',
                        'mail_host' => $settings['mail_host'] ?? 'not set'
                    ]);
                    
                    if ($settings) {
                        // Normalize mail driver to lowercase (Laravel expects 'smtp' not 'SMTP')
                        $mailDriver = 'smtp'; // Default
                        if (!empty($settings['mail_driver'])) {
                            $mailDriver = strtolower(trim($settings['mail_driver']));
                            // Ensure it's a valid mailer name
                            if (!in_array($mailDriver, ['smtp', 'mail', 'sendmail', 'log', 'array'])) {
                                $mailDriver = 'smtp';
                            }
                        }
                        
                        config([
                            'mail.default' => $mailDriver,
                            'mail.mailers.smtp.host' => $settings['mail_host'] ?? '',
                            'mail.mailers.smtp.port' => $settings['mail_port'] ?? '',
                            'mail.mailers.smtp.encryption' => $settings['mail_encryption'] ?? '',
                            'mail.mailers.smtp.username' => $settings['mail_username'] ?? '',
                            'mail.mailers.smtp.password' => $settings['mail_password'] ?? '',
                            'mail.from.address' => $settings['mail_from_address'] ?? config('mail.from.address'),
                            'mail.from.name' => $settings['mail_from_name'] ?? config('mail.from.name'),
                        ]);
                        
                        \Log::info('Mail configuration set', [
                            'mail_driver' => $mailDriver,
                            'mail_host' => config('mail.mailers.smtp.host'),
                            'mail_port' => config('mail.mailers.smtp.port'),
                            'from_address' => config('mail.from.address')
                        ]);
                    } else {
                        \Log::warning('No SMTP settings found, using default configuration');
                    }
                    
                    \Log::info('Attempting to send email', [
                        'to' => $booking->primary_applicant_email,
                        'from' => config('mail.from.address'),
                        'from_name' => config('mail.from.name'),
                        'mail_driver' => config('mail.default'),
                        'smtp_host' => config('mail.mailers.smtp.host'),
                        'smtp_port' => config('mail.mailers.smtp.port')
                    ]);
                    
                    // Create mailable instance
                    $mailable = new BookingConfirmation($booking, $employee, $project, $firstPayment, '+91 99231 96779');
                    
                    // Send email synchronously (not queued)
                    // The default mailer is already set in config above
                    Mail::to($booking->primary_applicant_email)->send($mailable);
                    
                    \Log::info('Booking confirmation email sent successfully', [
                        'booking_id' => $booking->id,
                        'email' => $booking->primary_applicant_email
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Failed to send booking confirmation email', [
                        'booking_id' => $booking->id,
                        'email' => $booking->primary_applicant_email,
                        'error' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    // Don't fail the booking creation if email fails
                }
            } else {
                \Log::warning('Email not sent - primary_applicant_email is empty', [
                    'booking_id' => $booking->id
                ]);
            }

            \DB::commit();

            \Log::info('Booking created successfully', [
                'booking_id' => $booking->id,
                'enquiry_id' => $enquiry->id,
                'unit_id' => $unit->id
            ]);

            return redirect()->route('booking.all')
                ->with('success', 'Booking successfully created.');

        } catch (\Exception $e) {
            \DB::rollBack();
            
            \Log::error('Booking creation failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request' => $request->except(['_token', 'payment_json'])
            ]);

            return redirect()->back()
                ->with('error', 'Error creating booking: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    public function update(Request $request, BookingForm $bookingForm)
    {
        // Allow Finance & Accounts to edit any booking IN ADDITION to normal permissions
        $canEdit = Auth::user()->can('Edit TimeSheet') || 
                   $this->isFinanceAccountsUser(Auth::user());
        
        if ($canEdit) {
            \Log::info('Booking form update started', $request->all());

            // Convert array fields to single values if needed
            $request->merge([
                'booking_date' => is_array($request->booking_date) ? $request->booking_date[0] : $request->booking_date,
            ]);

            $validator = Validator::make($request->all(), [
                // Your validation rules here
            ]);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            try {
                // If employee_id is empty, do not overwrite existing employee_id.
                // Also enforce: cannot assign booking to terminated employee.
                if ($request->has('employee_id')) {
                    if (empty($request->employee_id)) {
                        $request->request->remove('employee_id');
                    } else {
                        $this->ensureNotTerminatedEmployeeUserId($request->employee_id);
                    }
                }

                // Prepare Payment Data
                $paymentData = [];
                // Handle payment JSON data
                if ($request->has('payment_json') && !empty($request->payment_json)) {
                    $paymentData = json_decode($request->payment_json, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new \Exception('Invalid payment JSON format: ' . json_last_error_msg());
                    }
                } else {
                    // Fallback to individual payment fields
                    if ($request->has('mode') && is_array($request->mode)) {
                        foreach ($request->mode as $key => $mode) {
                            $amount = $request->amount[$key] ?? 0;
                            if (!is_numeric($amount) || $amount <= 0) {
                                continue; // Skip invalid payments
                            }

                            $paymentData[] = [
                                'mode' => $mode ?? 'cash',
                                'date' => $request->payment_date[$key] ?? $request->booking_date ?? now()->format('Y-m-d'),
                                'payment_detail' => $request->payment_detail[$key] ?? null,
                                'amount' => (float)$amount,
                            ];
                        }
                    }
                }

                // Calculate financials based on project type
                $projectType = $request->project_type;
                $totalCost = 0;
                $agreementCost = 0;

                if ($projectType == 3) { // Plotting project
                    // Calculate agreement cost
                    $basicCost = (float)($request->basic_cost ?? 0);
                    $costInfrastructure = (float)($request->cost_infrastructure ?? 0);
                    $agreementCost = $basicCost + $costInfrastructure;

                    // Calculate total cost
                    $gst = (float)($request->gst ?? 0);
                    $stampDuty = (float)($request->stamp_duty ?? 0);
                    $registration = (float)($request->registration ?? 0);
                    $legalCharges = (float)($request->legal_charges ?? 0);
                    $other = (float)($request->other ?? 0);
                    $maintenanceCost = (float)($request->maintenance_cost ?? 0);

                    $totalCost = $agreementCost + $gst + $stampDuty + $registration + 
                                $legalCharges + $other + $maintenanceCost;

                } else if ($projectType == 1 || $projectType == 2) { // Residential or Commercial
                    // Calculate agreement cost
                    $builtUpArea = (float)($request->built_up_area ?? 0);
                    $ratePerSqFt = (float)($request->rate_per_sq_ft ?? 0);
                    $costInfrastructure = (float)($request->cost_infrastructure ?? 0);
                    
                    $basicCost = $builtUpArea * $ratePerSqFt;
                    $agreementCost = $basicCost + $costInfrastructure;

                    // Calculate taxes based on project type
                    $gstRate = ($projectType == 1) ? 0.01 : 0.12; // Residential 1%, Commercial 12%
                    $stampDutyRate = 0.06; // 6%

                    $gst = $agreementCost * $gstRate;
                    $stampDuty = $agreementCost * $stampDutyRate;

                    // Calculate total cost
                    $legalCharges = (float)($request->legal_charges ?? 0);
                    $other = (float)($request->other ?? 0);
                    $maintenanceCost = (float)($request->maintenance_cost ?? 0);

                    $totalCost = $agreementCost + $gst + $stamp_duty + $registration + 
                                $legalCharges + $other + $maintenanceCost;
                }

                // Calculate paid amount and remaining
                $paidAmount = collect($paymentData)->sum('amount');
                $remaining = $totalCost - $paidAmount;

                // Get project and unit details
                $project = Project::find($request->project_id);
                $unit = Unit::find($request->unit_id);

                // Prepare booking data
                $bookingData = $request->except([
                                'mode', 
                                'payment_detail', 
                                'amount', 
                                '_token',
                                '_method',
                                'payment_date',
                                'payment_json'
                            ]);

                // Add calculated fields
                $bookingData['project_name'] = $project ? $project->project_name : null;
                $bookingData['unit_name'] = $unit ? $unit->unit_name : null;
                $bookingData['agreement_cost'] = $agreementCost;
                $bookingData['total_cost'] = $totalCost;
                $bookingData['payment_data'] = !empty($paymentData) ? json_encode($paymentData) : null;
                $bookingData['remaining'] = $remaining > 0 ? $remaining : null;

                // Handle unit change if needed
                if ($request->unit_id != $bookingForm->unit_id) {
                    // Release old unit (make it available again)
                    $oldUnit = Unit::find($bookingForm->unit_id);
                    if ($oldUnit) {
                        $oldUnit->is_approved = 0;  // ✅ Set to 0 to make it available
                        $oldUnit->save();
                    }

                    // Book new unit (mark it as unavailable)
                    $newUnit = Unit::find($request->unit_id);
                    if ($newUnit) {
                        $newUnit->is_approved = 1;  // ✅ Set to 1 to mark it as booked
                        $newUnit->save();
                    }
                }

                // Update booking
                $bookingForm->update($bookingData);

                return redirect()->route('booking.index')
                    ->with('success', __('Booking successfully updated.'));

            } catch (\Exception $e) {
                \Log::error('Booking update failed', ['error' => $e->getMessage()]);
                return redirect()->back()
                    ->with('error', 'Error updating booking: '.$e->getMessage())
                    ->withInput();
            }
        }

        return redirect()->back()->with('error', 'Permission denied.');
    }
    
    public function destroy($id)
    {
        $canDelete = Auth::user()->can('Delete TimeSheet') || 
                     $this->isFinanceAccountsUser(Auth::user());
        
        if (!$canDelete) {
            return redirect()->back()->with('error', 'Permission denied.');
        }

        DB::beginTransaction();
    
        try {
            $booking = BookingForm::findOrFail($id);
            $unit = Unit::find($booking->unit_id);
            
            if ($unit) {
                $unit-> is_approved = 1;
                $unit->save();
            }
    
            $booking->delete();
    
            DB::commit();
    
            return redirect()->route('booking.index')
                ->with('success', 'Booking deleted successfully!');
    
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error deleting booking: '.$e->getMessage());
        }
    }
    

    public function show(BookingForm $bookingForm)
    {
        // No permission checks - allow all authenticated users to view bookings
        $projectName = $bookingForm->project_name;
        $unitName = $bookingForm->unit_name;
        return view('booking.show', compact('bookingForm','projectName', 'unitName'));
    }


    public function pdf($id)
    {
        try {
            $booking = BookingForm::with(['project', 'employee', 'unit'])
                ->findOrFail($id);
                
            // Check if user has permission to view this booking
            $isFinanceAccounts = $this->isFinanceAccountsUser(Auth::user());
            $isSiteHead = $this->isSiteHead(Auth::user()->employee->id ?? null, $booking->project);
            
            if (Auth::user()->type == 'employee' && 
                $booking->employee_id != Auth::user()->id && 
                !$isSiteHead &&
                !$isFinanceAccounts) {
                abort(403, 'Unauthorized access.');
            }
            
            // Generate HTML content
            $html = view('booking.pdf', compact('booking'))->render();
            
            // Use mPDF
            $mpdf = new \Mpdf\Mpdf();
            $mpdf->WriteHTML($html);
            
            // Set filename
            $filename = "booking-form-{$booking->id}-" . date('Y-m-d') . ".pdf";
            
            // Output the PDF for download
            return response()->make(
                $mpdf->Output($filename, 'D'),
                200,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="' . $filename . '"'
                ]
            );
            
        } catch (\Exception $e) {
            \Log::error('PDF generation failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to generate PDF: ' . $e->getMessage());
        }
    }

    public function print($id)
    {
        try {
            $booking = BookingForm::with(['project', 'employee', 'unit'])
                ->findOrFail($id);
                
            // Check if user has permission to view this booking
            $isFinanceAccounts = $this->isFinanceAccountsUser(Auth::user());
            $isSiteHead = $this->isSiteHead(Auth::user()->employee->id ?? null, $booking->project);
            
            if (Auth::user()->type == 'employee' && 
                $booking->employee_id != Auth::user()->id && 
                !$isSiteHead &&
                !$isFinanceAccounts) {
                abort(403, 'Unauthorized access.');
            }
            
            return view('booking.print', compact('booking'));
            
        } catch (\Exception $e) {
            \Log::error('Print view failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to load print view: ' . $e->getMessage());
        }
    }

    public function bookingFormPdf($id)
    {
        try {
            // Decrypt ID if needed
            try {
                $id = Crypt::decrypt($id);
            } catch (\Throwable $th) {
                return redirect()->back()->with('error', __('Permission Denied.'));
            }
            
            $booking = BookingForm::with(['project', 'employee', 'unit', 'timesheet'])
                ->findOrFail($id);
                
            // Check if user has permission to view this booking
            $isFinanceAccounts = $this->isFinanceAccountsUser(Auth::user());
            $isSiteHead = $this->isSiteHead(Auth::user()->employee->id ?? null, $booking->project);
            
            if (Auth::user()->type == 'employee' && 
                $booking->employee_id != Auth::user()->id && 
                !$isSiteHead &&
                !$isFinanceAccounts) {
                abort(403, 'Unauthorized access.');
            }
            
            // Check if PDF facade exists
            if (class_exists('Barryvdh\DomPDF\Facade\Pdf')) {
                $pdf = \PDF::loadView('booking.booking_form_pdf', compact('booking'));
                $filename = "booking-form-{$booking->id}-" . date('Y-m-d') . ".pdf";
                return $pdf->download($filename);
            } else {
                // Fallback to HTML download
                $html = view('booking.booking_form_pdf', compact('booking'))->render();
                $filename = "booking-form-{$booking->id}-" . date('Y-m-d') . ".html";
                
                return response()->make($html, 200, [
                    'Content-Type' => 'text/html',
                    'Content-Disposition' => 'attachment; filename="' . $filename . '"'
                ]);
            }
            
        } catch (\Exception $e) {
            \Log::error('PDF generation failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to generate PDF: ' . $e->getMessage());
        }
    }

    public function getUnitsByProject($project_id)
    {
        // Get only available units (is_approved = 0)
        $units = Unit::where('project_id', $project_id)
                    ->where('is_approved', 0) // Changed from 1 to 0
                    ->get(['id', 'unit_name', 'unit_size']);

        return response()->json(['units' => $units]);
    }

    public function getUnitDetails($unit_id)
    {
        $unit = Unit::find($unit_id, ['id', 'unit_name', 'unit_size']);
        
        return response()->json([
            'unit_name' => $unit->unit_name ?? null,
            'unit_size' => $unit->unit_size ?? null
        ]);
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

    public function editBooking(Request $request)
    {
        $user = Auth::user();
        $projects = [];

        // ADMIN / COMPANY USER → all projects
        if ($user->type == 'company') {
            $projects = Project::pluck('project_name', 'id');

        // FINANCE & ACCOUNTS → all projects
        } elseif ($this->isFinanceAccountsUser($user)) {
            $projects = Project::pluck('project_name', 'id');

        // EMPLOYEE → only assigned + site head projects
        } elseif ($user->type == 'employee') {
            $employee = Employee::where('user_id', $user->id)->first();

            if ($employee) {
                $allProjects = Project::all();

                foreach ($allProjects as $project) {
                    $isAssigned = false;

                    // 1️⃣ Check assigned_data (normal assignment)
                    if (!empty($project->assigned_data)) {
                        foreach ($project->assigned_data as $assignment) {
                            if (isset($assignment['employee_ids']) &&
                                is_array($assignment['employee_ids']) &&
                                in_array($employee->id, $assignment['employee_ids'])) {
                                $isAssigned = true;
                                break;
                            }
                        }
                    }

                    // 2️⃣ Check if employee is Site Head
                    if (!$isAssigned && !empty($project->site_heads)) {
                        $siteHeads = is_array($project->site_heads) ? $project->site_heads : json_decode($project->site_heads, true);
                        if (in_array($employee->id, $siteHeads)) {
                            $isAssigned = true;
                        }
                    }

                    if ($isAssigned) {
                        $projects[$project->id] = $project->project_name;
                    }
                }
            }
        }

        return view('booking.editbooking', compact('projects'));
    }

    public function loadBookingData(Request $request)
    {
        $canEdit = Auth::user()->can('Edit TimeSheet') || 
                   $this->isFinanceAccountsUser(Auth::user());
        
        if ($canEdit) {
            $booking = BookingForm::with(['project', 'unit', 'timesheet'])
                ->where('id', $request->booking_id)
                ->first();

            if (!$booking) {
                return response()->json(['error' => 'Booking not found'], 404);
            }

            // Get all units where is_approved = 1 (booked units)
            $bookedUnits = Unit::where('project_id', $booking->project_id)
                ->where('is_approved', 0)  // Booked units
                ->get(['id', 'unit_name', 'unit_size']);

            // Get the currently assigned unit (even if it's now available)
            $currentUnit = Unit::where('id', $booking->unit_id)
                ->get(['id', 'unit_name', 'unit_size']);

            // Combine both collections and remove duplicates
            $units = $bookedUnits->merge($currentUnit)->unique('id');

            // Decode payment data if it's not an array
            if (!is_array($booking->payment_data)) {
                $booking->payment_data = json_decode($booking->payment_data, true);
            }

            return response()->json([
                'booking' => $booking,
                'units' => $units,
                'project_type' => $booking->project->project_type ?? null,
                'project_name' => $booking->project->project_name ?? ''
            ]);
        }

        return response()->json(['error' => 'Permission denied'], 403);
    }

    public function getBookingsByProject($projectId)
    {
        $user = Auth::user();

        $bookingsQuery = BookingForm::where('project_id', $projectId)
            ->where('is_cancelled', 0);

        // Admin or Finance → all bookings
        if ($user->type == 'company' || $this->isFinanceAccountsUser($user)) {
            $bookings = $bookingsQuery->get(['id', 'primary_applicant_name', 'primary_applicant_contact_no']);
        } else {
            // Employee or Site Head
            $employee = Employee::where('user_id', $user->id)->first();
            if (!$employee) return response()->json(['bookings' => []], 403);

            $project = Project::find($projectId);
            if (!$project) return response()->json(['bookings' => []], 404);

            $siteHeads = is_array($project->site_heads) ? $project->site_heads : json_decode($project->site_heads, true);

            // Site Head → see all bookings of their projects
            if (!empty($siteHeads) && in_array($employee->id, $siteHeads)) {
                // No extra filter, show all bookings
            } else {
                // Regular employee → only their own bookings
                $bookingsQuery->where('employee_id', $employee->user_id); // <--- user_id from users table
            }

            $bookings = $bookingsQuery->get(['id', 'primary_applicant_name', 'primary_applicant_contact_no']);
        }

        return response()->json(['bookings' => $bookings]);
    }

    public function cancel(Request $request, BookingForm $bookingForm)
    {
        $canEdit = Auth::user()->can('Edit TimeSheet') || 
                   $this->isFinanceAccountsUser(Auth::user());
        
        if (!$canEdit) {
            return response()->json(['error' => 'Permission denied.'], 403);
        }

        \Log::info('Cancelling booking', ['booking_id' => $bookingForm->id]);

        try {
            \DB::beginTransaction();

            // Check if booking is already cancelled
            if ($bookingForm->is_cancelled) {
                throw new \Exception('This booking is already cancelled.');
            }

            // Mark booking as cancelled
            $bookingForm->is_cancelled = 1;
            $bookingForm->save();

            // Release the unit (set is_approved to 0 to make it available again)
            $unit = Unit::find($bookingForm->unit_id);
            if ($unit) {
                $unit->is_approved = 0;
                $unit->save();
                \Log::info('Unit released', ['unit_id' => $unit->id]);
            } else {
                \Log::warning('Unit not found for booking', ['unit_id' => $bookingForm->unit_id]);
            }

            \DB::commit();

            \Log::info('Booking cancelled successfully', ['booking_id' => $bookingForm->id]);

            return response()->json([
                'message' => 'Booking successfully cancelled.',
                'booking_id' => $bookingForm->id
            ]);

        } catch (\Exception $e) {
            \DB::rollBack();
            
            \Log::error('Booking cancellation failed', [
                'error' => $e->getMessage(),
                'booking_id' => $bookingForm->id
            ]);

            return response()->json([
                'error' => 'Error cancelling booking: ' . $e->getMessage()
            ], 500);
        }
    }

    public function markAgreementDone(Request $request, $id)
    {
        $canEdit = Auth::user()->can('Edit TimeSheet') || 
                   $this->isFinanceAccountsUser(Auth::user());
        
        if (!$canEdit) {
            return response()->json(['error' => 'Permission denied.'], 403);
        }

        try {
            $booking = BookingForm::findOrFail($id);
            $booking->agreement = 'done';
            $booking->save();

            return response()->json([
                'success' => true,
                'message' => 'Agreement marked as done successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function export(Request $request)
    {
        $canExport = Auth::user()->can('Export TimeSheet') || 
                     $this->isFinanceAccountsUser(Auth::user());
        
        if (!$canExport) {
            return redirect()->back()->with('error', 'Permission denied.');
        }

        try {
            // Replicate the same query logic from index method to get the filtered bookings
            $query = BookingForm::with(['employee', 'project', 'unit', 'timesheet']);

            // For employees, show their own bookings AND site head projects
            // Finance & Accounts can see ALL bookings in addition to existing rules
            if (Auth::user()->type == 'employee') {
                $userId = Auth::id();
                $employeeId = Auth::user()->employee->id ?? null;
                
                // Check if user is in Finance & Accounts department
                $isFinanceAccounts = $this->isFinanceAccountsUser(Auth::user());
                
                // If NOT Finance & Accounts, apply the normal filters
                if (!$isFinanceAccounts) {
                    $query->where(function($q) use ($userId, $employeeId) {
                        // Bookings where current user is the employee
                        $q->where('employee_id', $userId)
                        
                        // OR bookings for projects where user is site head (read-only access)
                        ->orWhereHas('project', function($projectQuery) use ($employeeId) {
                            $projectQuery->where(function($pq) use ($employeeId) {
                                $pq->whereJsonContains('site_heads', (string)$employeeId)
                                    ->orWhereJsonContains('site_heads', $employeeId)
                                    ->orWhereJsonContains('site_heads', (int)$employeeId);
                            });
                        });
                    });
                }
                // If user IS Finance & Accounts, they can see ALL bookings (no additional filtering needed)
            }

            // Apply all the same filters as the index page
            // Date filters
            if (!empty($request->start_date)) {
                $query->whereDate('booking_date', '>=', $request->start_date);
            }

            if (!empty($request->end_date)) {
                $query->whereDate('booking_date', '<=', $request->end_date);
            }

            // Filter by project if selected
            if (!empty($request->project)) {
                $query->where('project_id', $request->project);
            }

            // Combined status filter
            if (!empty($request->status)) {
                switch ($request->status) {
                    case 'active':
                        $query->where('is_cancelled', 0)->where('remaining', '>', 0);
                        break;
                    case 'completed':
                        $query->where('is_cancelled', 0)->where('remaining', '<=', 0);
                        break;
                    case 'cancelled':
                        $query->where('is_cancelled', 1);
                        break;
                    case 'agreement_done':
                        $query->where('agreement', 'done');
                        break;
                }
            }

            // Add any additional filters that might be on your index page
            // For example, search filter
            if (!empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('primary_applicant_name', 'like', "%{$search}%")
                    ->orWhere('primary_applicant_contact_no', 'like', "%{$search}%")
                    ->orWhereHas('project', function($projectQuery) use ($search) {
                        $projectQuery->where('project_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('unit', function($unitQuery) use ($search) {
                        $unitQuery->where('unit_name', 'like', "%{$search}%");
                    });
                });
            }

            // Get the filtered results
            $bookings = $query->get();

            // Generate filename with timestamp and filter info
            $filename = 'bookings_export_' . date('Y-m-d_H-i-s') . '.xlsx';

            return Excel::download(new BookingsExport($bookings), $filename);

        } catch (\Exception $e) {
            \Log::error('Export failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Export failed: ' . $e->getMessage());
        }
    }
}