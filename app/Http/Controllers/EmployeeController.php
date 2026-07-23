<?php

    namespace App\Http\Controllers;

    use App\Models\Branch;
    use App\Models\Site;
    use App\Models\Department;
    use App\Models\Designation;
    use App\Models\Document;
    use App\Models\Employee;
    use App\Models\EmployeeDocument;
    use App\Mail\UserCreate;
    use App\Mail\EmployeeCreate;
    use App\Models\Plan;
    use App\Models\User;
    use App\Models\Utility;
    use App\Models\Resignation;
    use App\Models\CompanyPolicy;
    use App\Models\PolicyAcknowledgement;
    use File;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Crypt;
    use Illuminate\Support\Facades\Hash;
    use Illuminate\Support\Facades\Mail;
    use App\Models\JoiningLetter;
    use App\Imports\EmployeesImport;
    use App\Exports\EmployeesExport;
    use App\Models\Contract;
    use App\Models\ExperienceCertificate;
    use App\Models\LoginDetail;
    use Maatwebsite\Excel\Facades\Excel;
    use App\Models\NOC;
    use App\Models\PaySlip;
    use App\Models\Termination;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Validation\Rule;
    use App\Models\DailyQuote;  

    //use Faker\Provider\File;

    class EmployeeController extends Controller
    {
    
        /**
         * Display a listing of the resource.
         *
         * @return \Illuminate\Http\Response
         */
        public function index()
        {
            $user = \Auth::user();
            
            // Check if user has ANY level of view access
            if (!$user->hasAnyPermission(['employee.view.all']) && $user->type == 'employee') {
                return redirect()->back()->with('error', __('Permission denied.'));
            }

            $query = Employee::with(['branch', 'department', 'designation', 'user'])
                ->whereNotIn('id', function($q) {
                    $q->select('employee_id')->from('terminations');
                });

            $leftQuery = Employee::with(['branch', 'department', 'designation', 'user'])
                ->whereIn('id', function($q) {
                    $q->select('employee_id')->from('terminations');
                });

            // Apply Scope Filtering
            if ($user->type == 'employee' && !$user->can('employee.view.all')) {
                // Technically this block won't be hit because we restrict 'employee' users who don't have 'employee.view.all' above,
                // but just in case, we enforce OWN data only.
                $query->where('user_id', $user->id);
                $leftQuery->where('user_id', $user->id);
            }

            $activeEmployees = $query->get()->map(function ($employee) use ($user) {
                $employee->formatted_id = $user->employeeIdFormat($employee->employee_id);
                return $employee;
            });

            $leftEmployees = $leftQuery->get()->map(function ($employee) use ($user) {
                $employee->formatted_id = $user->employeeIdFormat($employee->employee_id);
                return $employee;
            });

            return view('employee.index', compact('activeEmployees', 'leftEmployees'));
        }

        public function downloadEducationDocument($filename)
        {
            $filePath = storage_path('app/public/uploads/education_images/' . $filename);
            
            if (file_exists($filePath)) {
                return response()->file($filePath);
            }
            
            abort(404, 'File not found');
        }

        // public function index()
        // {
        //     if (\Auth::user()->can('Manage Employee') || \Auth::user()->isHR()) {
        //         if (Auth::user()->type == 'employee') {
        //             $employees = Employee::where('user_id', Auth::user()->id)
        //                 ->with(['branch', 'department', 'designation', 'user'])
        //                 ->get();
        //         } else {
        //             $employees = Employee::where('created_by', Auth::user()->id) // or creatorId()
        //             ->with(['branch', 'department', 'designation', 'user'])
        //             ->get()
        //             ->map(function ($employee) {
        //                 $employee->formatted_id = Auth::user()->employeeIdFormat($employee->employee_id);
        //                 return $employee;
        //             });

        //         }
            
        //         return view('employee.index', compact('employees'));
        //     } else {
        //         return redirect()->back()->with('error', __('Permission denied.'));
        //     }
        // }

        public function create()
        {
            if (\Auth::user()->can('employee.create.all') || \Auth::user()->isHR()) {
                $company_settings = Utility::settings();
                $documents        = Document::where('created_by', \Auth::user()->creatorId())->get();
                $branches         = Branch::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
                $branches->prepend('Select Branch', '');
                $departments      = Department::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
                $departments->prepend('Select Department', '');
                $designations     = Designation::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
                $designations->prepend('Select Designation', '');
                $employees        = User::where('created_by', \Auth::user()->creatorId())->get();
                $employeesId      = \Auth::user()->employeeIdFormat($this->employeeNumber());


                return view('employee.create', compact('employees', 'employeesId', 'departments', 'designations', 'documents', 'branches', 'company_settings'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        }

        public function store(Request $request)
        {
            if (\Auth::user()->can('Create Employee') || \Auth::user()->isHR()) {
                $rules = [
                    'name' => 'required',
                    'last_name' => 'required',
                    'email' => 'required|unique:users',
                    'password' => 'required',
                    'branch_id' => 'required',
                    'department_id' => 'required',
                    'designation_id' => 'required',
                    'document.*' => 'required',
                ];

                    $messages = [
                    'education.*.document.max' => 'The education document must not be greater than 10MB.',
                    'education.*.document.mimes' => 'The education document must be a file of type: pdf, doc, docx, jpg, jpeg, png.',
                ];

                $validator = \Validator::make($request->all(), $rules);

                if ($validator->fails()) {
                    return redirect()->back()->withInput()->with('error', $validator->messages()->first());
                }

                // Process education details
                $educationDetails = [];
                $educationImages = [];
                
                if ($request->has('education')) {
                    foreach ($request->education as $key => $education) {
                        if (!empty($education['college_name'])) {
                            $docPath = null;
                            
                            // Handle document upload
                            if (isset($education['document']) && $education['document']) {
                                $docPath = $this->storeEducationDocument($education['document'], $request->employee_id, $key);
                            }
                            
                            $educationDetails[] = [
                                'college_name' => $education['college_name'],
                                'passing_year' => $education['passing_year'] ?? null,
                                'grade' => $education['grade'] ?? null,
                                'degree' => $education['degree'] ?? null,
                                'document_path' => $docPath,
                            ];
                            
                            if ($docPath) {
                                $educationImages[] = $docPath;
                            }
                        }
                    }
                }

                // Process experience details
                $experienceDetails = [];
                if ($request->has('experience')) {
                    foreach ($request->experience as $experience) {
                        if (!empty($experience['previous_company_name'])) {
                            $experienceDetails[] = [
                                'previous_company_name' => $experience['previous_company_name'],
                                'previous_designation' => $experience['previous_designation'] ?? null,
                                'start_date' => $experience['start_date'] ?? null,
                                'end_date' => $experience['end_date'] ?? null,
                                'previous_salary' => $experience['previous_salary'] ?? null,
                            ];
                        }
                    }
                }

                // Combine name fields
                $fullName = trim(($request['name'] ?? '') . ' ' . ($request['middle_name'] ?? '') . ' ' . ($request['last_name'] ?? ''));
                $fullName = preg_replace('/\s+/', ' ', $fullName); // Remove extra spaces

                // Store plain text password for email
                $plainPassword = $request['password'];

                // Create user
                $user = User::create([
                    'name' => $fullName,
                    'email' => $request['email'],
                    'password' => Hash::make($request['password']),
                    'type' => 'employee',
                    'created_by' => \Auth::user()->creatorId(),
                    'email_verified_at' => now(),
                ]);
                $user->assignRole('Employee');

                // Auto-assign default own permissions to the new employee
                $permissions = \Spatie\Permission\Models\Permission::where('name', 'like', '%.own')->pluck('name')->toArray();
                $excluded = [
                    'attendance.bulk.view.own',
                    'attendance.biometric.view.own',
                    'leave.details.view.own',
                    'payroll.payable_days.view.own',
                ];
                $permissionsToAssign = array_diff($permissions, $excluded);
                $user->givePermissionTo($permissionsToAssign);

                // Create employee with all details
                $employee = Employee::create([
                    'user_id' => $user->id,
                    'name' => $fullName,
                    'name' => $request['name'] ?? null,
                    'middle_name' => $request['middle_name'] ?? null,
                    'last_name' => $request['last_name'] ?? null,
                    'dob' => $request['dob'] ?? null,
                    'blood_group' => $request['blood_group'] ?? null,
                    'gender' => $request['gender'] ?? 'Male', // Default value
                    'phone' => $request['phone'] ?? null,
                    'address' => $request['address'] ?? null,
                    'email' => $request['email'],
                    'password' => Hash::make($request['password']),
                    'employee_id' => $this->employeeNumber(),
                    'branch_id' => $request['branch_id'],
                    'department_id' => $request['department_id'],
                    'designation_id' => $request['designation_id'],
                    'work_location' => $request['work_location'] ?? 'Pune',
                    'company_doj' => $request['company_doj'] ?? now(), // Default to current date
                    'office_phone_one' => $request['office_phone_one'] ?? null,
                    'office_phone_two' => $request['office_phone_two'] ?? null,
                    'emergency_number' => $request['emergency_number'] ?? null,
                    'week_off_day' => $request['week_off_day'] ?? null,
                    'education_details' => json_encode($educationDetails ?? []),
                    'education_images' => json_encode($educationImages),
                    'experience_details' => json_encode($experienceDetails ?? []),
                    'account_holder_name' => $request['account_holder_name'] ?? null,
                    'bank_name' => $request['bank_name'] ?? null,
                    'bank_identifier_code' => $request['bank_identifier_code'] ?? null,
                    'branch_location' => $request['branch_location'] ?? null,
                    'account_number' => $request['account_number'] ?? null,
                    'account_type' => $request['account_type'] ?? null,
                    'created_by' => \Auth::user()->creatorId(),
                ]);

                // Load relationships for email
                $employee->load(['branch', 'department', 'designation']);

                // Automatically allocate initial leaves for the joining month
                try {
                    $joinDate = \Carbon\Carbon::parse($employee->company_doj ?? now());
                    $creatorId = \Auth::user()->creatorId();
                    
                    $earnedLeaveType = \App\Models\LeaveType::firstOrCreate(
                        ['title' => 'Earned Leave', 'created_by' => $creatorId],
                        ['days' => 1.0]
                    );
                    
                    $sickLeaveType = \App\Models\LeaveType::firstOrCreate(
                        ['title' => 'Sick Leave', 'created_by' => $creatorId],
                        ['days' => 1.0]
                    );

                    // Allocate Earned Leave
                    \App\Models\EmployeeLeaveBalance::firstOrCreate(
                        [
                            'employee_id' => $employee->id,
                            'leave_type_id' => $earnedLeaveType->id,
                            'year' => $joinDate->year,
                            'month' => $joinDate->month,
                        ],
                        [
                            'allocated_days' => 1.0,
                            'used_days' => 0,
                            'carry_forward_days' => 0
                        ]
                    );

                    // Allocate Sick Leave
                    \App\Models\EmployeeLeaveBalance::firstOrCreate(
                        [
                            'employee_id' => $employee->id,
                            'leave_type_id' => $sickLeaveType->id,
                            'year' => $joinDate->year,
                            'month' => $joinDate->month,
                        ],
                        [
                            'allocated_days' => 1.0,
                            'used_days' => 0,
                            'carry_forward_days' => 0
                        ]
                    );
                } catch (\Exception $e) {
                    \Log::error('Failed to allocate initial leaves: ' . $e->getMessage());
                }

                // Format employee ID for email
                $formattedEmployeeId = \Auth::user()->employeeIdFormat($employee->employee_id);

                // We are no longer configuring mail settings from the database for Employee Creation.
                // The system will automatically fall back to using the SMTP credentials from the .env file.
                // config(['mail.default' => 'smtp']);
                // Send email to employee
                try {
                    Mail::to($request['email'])->send(new EmployeeCreate($user, $employee, $plainPassword, $formattedEmployeeId));
                } catch (\Exception $e) {
                    // Log error but don't fail the employee creation
                    \Log::error('Failed to send employee creation email', [
                        'email' => $request['email'],
                        'error' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }

                return redirect()->route('employee.index')->with('success', __('Employee successfully created.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        }

        public function resendEmail($id)
        {
            if (\Auth::user()->can('employee.create.all') || \Auth::user()->can('employee.edit.all') || \Auth::user()->can('employee.send_mail.all') || \Auth::user()->isHR()) {
                $employee = Employee::findOrFail($id);
                $user = User::where('id', '=', $employee->user_id)->first();
                
                if (!$user) {
                    return redirect()->back()->with('error', __('User not found.'));
                }

                // Generate a new random password
                $plainPassword = \Illuminate\Support\Str::random(8);
                $user->password = \Hash::make($plainPassword);
                $user->save();

                $formattedEmployeeId = \Auth::user()->employeeIdFormat($employee->employee_id);
                
                // Send email to employee
                try {
                    Mail::to($user->email)->send(new EmployeeCreate($user, $employee, $plainPassword, $formattedEmployeeId));
                } catch (\Exception $e) {
                    \Log::error('Failed to resend employee creation email', [
                        'email' => $user->email,
                        'error' => $e->getMessage(),
                    ]);
                    return redirect()->back()->with('error', __('Email failed to send. Check your mail settings.'));
                }

                return redirect()->back()->with('success', __('Welcome email successfully sent.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        }
   
        public function edit($id)
        {
            try {
                $employeeId = Crypt::decrypt($id);
                $employee = Employee::with(['branch', 'department', 'designation', 'documents.document'])
                    ->findOrFail($employeeId);

                if (!\Auth::user()->can('employee.edit.all') && !\Auth::user()->isHR() && !(\Auth::user()->can('employee.profile.edit.own') && \Auth::user()->id == $employee->user_id)) {
                    return redirect()->back()->with('error', __('Permission denied.'));
                }

                // ✅ Prevent employees from editing after approval
                if ($employee->approval_status === 'approved' && \Auth::user()->type === 'employee' && !\Auth::user()->isHR()) {
                    return redirect()->route('employee.show', $id)
                        ->with('error', __('Your details have been approved and can no longer be edited.'));
                }

                // Safely decode JSON fields
                $experiences = [];
                $educations = [];

                if (!empty($employee->experience_details)) {
                    try {
                        $experiences = is_array($employee->experience_details) 
                            ? $employee->experience_details 
                            : json_decode($employee->experience_details, true);
                        $experiences = $experiences ?: [];
                    } catch (\Exception $e) {
                        \Log::error("Error decoding experiences: " . $e->getMessage());
                        $experiences = [];
                    }
                }

                if (!empty($employee->education_details)) {
                    try {
                        $educations = is_array($employee->education_details) 
                            ? $employee->education_details 
                            : json_decode($employee->education_details, true);
                        $educations = $educations ?: [];
                    } catch (\Exception $e) {
                        \Log::error("Error decoding educations: " . $e->getMessage());
                        $educations = [];
                    }
                }

                // Get all necessary data
                $branches = Branch::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
                $departments = Department::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
                $designations = Designation::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
                $documents = Document::where('created_by', \Auth::user()->creatorId())->get();
                $companyPolicy = CompanyPolicy::where('created_by', \Auth::user()->creatorId())->get();
                $policyAcknowledgements = PolicyAcknowledgement::where('employee_id', $employee->id)->get()->keyBy('company_policy_id');

                return view('employee.edit', compact(
                    'employee',
                    'branches',
                    'departments',
                    'designations',
                    'documents',
                    'experiences',
                    'educations',
                    'companyPolicy',
                    'policyAcknowledgements'
                ));

            } catch (\Exception $e) {
                \Log::error("Employee edit error: " . $e->getMessage());
                return redirect()->back()->with('error', __('Employee not found'));
            }
        }
    
        public function update(Request $request, $id)
        {
            $employee = Employee::findOrFail($id);
            if (\Auth::user()->can('employee.edit.all') || \Auth::user()->isHR() || (\Auth::user()->can('employee.profile.edit.own') && \Auth::user()->id == $employee->user_id)) {

                // ✅ Prevent employees from updating after approval
                if (\Auth::user()->type === 'employee' && $employee->approval_status === 'approved' && !\Auth::user()->isHR()) {
                    return redirect()->back()->with('error', __('Your details have been approved and can no longer be edited.'));
                }

                $rules = [
                    'name' => 'required',
                    'last_name' => 'required',
                    'branch_id' => 'required',
                    'department_id' => 'required',
                    'designation_id' => 'required',
                ];

                // Add email validation only for company users (employees can't change email)
                if (\Auth::user()->type !== 'employee' || \Auth::user()->isHR()) {
                    $rules['email'] = [
                        'required',
                        'email',
                        Rule::unique('employees', 'email')->ignore($employee->id),
                    ];
                }

                $validator = \Validator::make($request->all(), $rules);

                if ($validator->fails()) {
                    return redirect()->back()->withInput()->with('error', $validator->messages()->first());
                }

                // Process education details
                $educationDetails = [];
                $educationImages = [];
                if ($request->has('education')) {
                    foreach ($request->education as $key => $education) {
                        if (!empty($education['college_name'])) {
                            $docPath = $education['existing_document'] ?? null;

                            if (isset($education['document']) && $education['document']) {
                                if ($docPath && file_exists(storage_path('app/public/' . $docPath))) {
                                    unlink(storage_path('app/public/' . $docPath));
                                }
                                $docPath = $this->storeEducationDocument($education['document'], $employee->employee_id, $key);
                            }

                            $educationDetails[] = [
                                'college_name' => $education['college_name'],
                                'passing_year' => $education['passing_year'] ?? null,
                                'grade' => $education['grade'] ?? null,
                                'degree' => $education['degree'] ?? null,
                                'document_path' => $docPath,
                            ];

                            if ($docPath) {
                                $educationImages[] = $docPath;
                            }
                        }
                    }
                }

                // Process experience details
                $experienceDetails = [];
                if ($request->has('experience')) {
                    foreach ($request->experience as $key => $experience) {
                        if (!empty($experience['previous_company_name'])) {
                            $experienceDetails[] = [
                                'previous_company_name' => $experience['previous_company_name'],
                                'previous_designation' => $experience['previous_designation'] ?? null,
                                'start_date' => $experience['start_date'] ?? null,
                                'end_date' => $experience['end_date'] ?? null,
                                'previous_salary' => $experience['previous_salary'] ?? null,
                            ];
                        }
                    }
                }

                // Combine name fields
                $fullName = trim(($request['name'] ?? '') . ' ' . ($request['middle_name'] ?? '') . ' ' . ($request['last_name'] ?? ''));
                $fullName = preg_replace('/\s+/', ' ', $fullName); // Remove extra spaces

                $data = [
                    'name' => $fullName,
                    'name' => $request['name'] ?? null,
                    'middle_name' => $request['middle_name'] ?? null,
                    'last_name' => $request['last_name'] ?? null,
                    'dob' => $request['dob'] ?? null,
                    'blood_group' => $request['blood_group'] ?? null,
                    'work_location' => $request['work_location'] ?? 'Pune',
                    'gender' => $request['gender'] ?? null,
                    'phone' => $request['phone'] ?? null,
                    'address' => $request['address'] ?? null,
                    'branch_id' => $request['branch_id'],
                    'department_id' => $request['department_id'],
                    'designation_id' => $request['designation_id'],
                    'company_doj' => $request['company_doj'] ?? null,
                    'office_phone_one' => $request['office_phone_one'] ?? null,
                    'office_phone_two' => $request['office_phone_two'] ?? null,
                    'emergency_number' => $request['emergency_number'] ?? null,
                    'education_details' => json_encode($educationDetails),
                    'education_images' => json_encode($educationImages),
                    'experience_details' => json_encode($experienceDetails),
                    'week_off_day' => $request['week_off_day'] ?? null,
                    'comp_off_enabled' => $request->has('comp_off_enabled') ? 1 : 0,
                    'account_holder_name' => $request['account_holder_name'] ?? null,
                    'bank_name' => $request['bank_name'] ?? null,
                    'bank_identifier_code' => $request['bank_identifier_code'] ?? null,
                    'branch_location' => $request['branch_location'] ?? null,
                    'account_number' => $request['account_number'] ?? null,
                    'account_type' => $request->has('account_type') 
                        ? ($request['account_type'] ?: null) 
                        : ($employee->account_type ?? null),
                ];

                // ✅ Reset approval if employee edits details
                if (\Auth::user()->type === 'employee') {
                    $data['approval_status'] = 'pending';
                    $data['approved_at'] = null;
                    $data['approved_by'] = null;
                    $data['rejection_reason'] = null;
                }

                // ✅ Handle email update - only company users can change email
                if (\Auth::user()->type !== 'employee' && $request->has('email')) {
                    $data['email'] = $request['email'];
                    // Also update the user's email
                    $user = User::find($employee->user_id);
                    if ($user) {
                        $user->email = $request['email'];
                        $user->save();
                    }
                } else {
                    // Keep existing email for employees (prevent them from changing it)
                    $data['email'] = $employee->email;
                }

                // Update password if provided
                if (!empty($request->password)) {
                    $data['password'] = Hash::make($request['password']);
                    $user = User::find($employee->user_id);
                    if ($user) {
                        $user->password = Hash::make($request['password']);
                        $user->save();
                    }
                }

                // Handle document uploads
                if ($request->has('document')) {
                    foreach ($request->document as $docId => $document) {
                        $employeeDocument = EmployeeDocument::where('employee_id', $employee->id)
                            ->where('document_id', $docId)
                            ->first();

                        if (!$employeeDocument) {
                            $employeeDocument = new EmployeeDocument();
                            $employeeDocument->employee_id = $employee->id;
                            $employeeDocument->document_id = $docId;
                        } else {
                            $oldFilePath = storage_path('app/public/' . $employeeDocument->document_value);
                            if (file_exists($oldFilePath)) {
                                unlink($oldFilePath);
                            }
                        }

                        $filename = $employee->employee_id . '_' . $docId . '_' . time() . '.' . $document->getClientOriginalExtension();
                        $dir = 'uploads/document';
                        $document->move(storage_path('app/public/' . $dir), $filename);

                        $employeeDocument->document_value = $dir . '/' . $filename;
                        $employeeDocument->save();
                    }
                }

                $employee->update($data);

                if (\Auth::user()->hasRole('employee')) {
                    return redirect()->route('employee.show', Crypt::encrypt($employee->id))
                        ->with('success', __('Employee successfully updated.'));
                } else {
                    return redirect()->route('employee.index')
                        ->with('success', __('Employee successfully updated.'));
                }
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        }

                
        public function destroy($id)
        {
            if (Auth::user()->can('Delete Employee') || \Auth::user()->isHR()) {
                $employee      = Employee::findOrFail($id);
                $user          = User::where('id', '=', $employee->user_id)->first();
                $emp_documents = EmployeeDocument::where('employee_id', $employee->employee_id)->get();
                $ContractEmployee = Contract::where('employee_name', '=', $employee->user_id)->get();
                $payslips = PaySlip::where('employee_id', $id)->get();
                $employee->delete();
                $user->delete();

                foreach ($ContractEmployee as $contractdelete) {
                    $contractdelete->delete();
                }

                foreach ($payslips as $payslip) {
                    $payslip->delete();
                }

                $dir = storage_path('app/public/uploads/document/');
                foreach ($emp_documents as $emp_document) {
                    $emp_document->delete();
                    if (!empty($emp_document->document_value)) {
                        $file_path = $emp_document->document_value;
                        $result = Utility::changeStorageLimit(\Auth::user()->creatorId(), $file_path);
                        if (\Storage::disk('public')->exists($file_path)) {
                            \Storage::disk('public')->delete($file_path);
                        }
                    }
                }

                return redirect()->route('employee.index')->with('success', 'Employee successfully deleted.');
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        }



        public function show($id)
        {
            try {
                $empId = Crypt::decrypt($id);
            } catch (\RuntimeException $e) {
                return redirect()->back()->with('error', __('Employee not available'));
            }

            $employee = Employee::with(['branch', 'department', 'designation', 'user'])->find($empId);
            
            if (!$employee) {
                $employee = Employee::where('user_id', $empId)->with(['branch', 'department', 'designation', 'user'])->first();
            }

            if (!$employee) {
                return redirect()->back()->with('error', __('Employee not found'));
            }

            if (\Auth::user()->can('employee.show.all') || \Auth::user()->isHR() || (\Auth::user()->can('employee.profile.view.own') && \Auth::user()->id == $employee->user_id)) {

                // Safely access relationships
                $documents = Document::where('created_by', \Auth::user()->creatorId())->get();
                $branches = Branch::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
                $departments = Department::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
                $designations = Designation::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
                
                $employeesId = \Auth::user()->employeeIdFormat($employee->employee_id);

                $experienceDetails = [];
                if (!empty($employee->experience_details)) {
                    $experienceDetails = json_decode($employee->experience_details, true);
                }

                $educationDetails = [];
                if (!empty($employee->education_details)) {
                    $educationDetails = json_decode($employee->education_details, true);
                }

                $companyPolicy = CompanyPolicy::where('created_by', \Auth::user()->creatorId())->get();
                $policyAcknowledgements = PolicyAcknowledgement::where('employee_id', $employee->id)->get()->keyBy('company_policy_id');

                return view('employee.show', compact('employee', 'employeesId', 'branches', 'departments', 'designations', 'documents', 'experienceDetails', 'educationDetails', 'companyPolicy', 'policyAcknowledgements'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        }



        function employeeNumber()
        {
            $latest = Employee::where('created_by', '=', \Auth::user()->creatorId())->latest('id')->first();
            if (!$latest) {
                return 1;
            }

            return $latest->id + 1;
        }

        public function exportModal()
        {
            if (!\Auth::user()->can('Create Employee') && !\Auth::user()->can('Export Employee') && !\Auth::user()->isHR()) {
                abort(403, 'Permission denied.');
            }

            $fields = [
                'employee_id' => 'Employee ID',
                'name' => 'Name',
                'dob' => 'Date of Birth',
                'blood_group' => 'Blood Group',
                'gender' => 'Gender',
                'phone' => 'Phone Number',
                'office_phone_one' => 'Office Phone One',
                'office_phone_two' => 'Office Phone Two',
                'emergency_number' => 'Emergency Number',
                'address' => 'Address',
                'email' => 'Email ID',
                'branch_id' => 'Branch',
                'department_id' => 'Department',
                'designation_id' => 'Designation',
                'education_details' => 'Education Details',
                'experience_details' => 'Experience Details',
                'company_doj' => 'Date of Join',
                'salary' => 'Salary',
                'week_off_day' => 'Week Off Day',
                'education_images' => 'Education Images',
            ];

            return view('employee.export_modal', compact('fields'));
        }

        public function export(Request $request)
        {
            if (!\Auth::user()->can('Create Employee') && !\Auth::user()->can('Export Employee') && !\Auth::user()->isHR()) {
                abort(403, 'Permission denied.');
            }

            $selectedFields = $request->input('fields');
            
            // If no fields are passed, it means no checkboxes were checked, or direct access.
            if (empty($selectedFields)) {
                return redirect()->back()->with('error', __('Please select at least one field to export.'));
            }

            $name = 'employee_' . date('Y-m-d i:h:s');
            return Excel::download(new EmployeesExport($selectedFields), $name . '.xlsx');
        }

        public function importFile()
        {
            return view('employee.import');
        }



        public function profile(Request $request)
        {
            if (\Auth::user()->can('Manage Employee Profile')) {
                $employees = Employee::where('created_by', \Auth::user()->creatorId())->with(['designation', 'user']);
                if (!empty($request->branch)) {
                    $employees->where('branch_id', $request->branch);
                }
                if (!empty($request->department)) {
                    $employees->where('department_id', $request->department);
                }
                if (!empty($request->designation)) {
                    $employees->where('designation_id', $request->designation);
                }
                $employees = $employees->get();

                $brances = Branch::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
                $brances->prepend('All', '');

                $departments = Department::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
                $departments->prepend('All', '');

                $designations = Designation::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
                $designations->prepend('All', '');

                return view('employee.profile', compact('employees', 'departments', 'designations', 'brances'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        }


        public function profileShow($id)
        {
            if (\Auth::user()->can('Show Employee Profile')) {
                $empId        = Crypt::decrypt($id);
                $documents    = Document::where('created_by', \Auth::user()->creatorId())->get();
                $branches     = Branch::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
                $departments  = Department::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
                $designations = Designation::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
                $employee     = Employee::find($empId);
                if ($employee == null) {
                    $employee     = Employee::where('user_id', $empId)->first();
                }

                $employeesId  = \Auth::user()->employeeIdFormat($employee->employee_id);

                return view('employee.show', compact('employee', 'employeesId', 'sites', 'branches', 'departments', 'designations', 'documents'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        }

        public function lastLogin(Request $request)
        {
            $users = User::where('created_by', \Auth::user()->creatorId())->get();

            $time = date_create($request->month);
            $firstDayofMOnth = (date_format($time, 'Y-m-d'));
            $lastDayofMonth =    \Carbon\Carbon::parse($request->month)->endOfMonth()->toDateString();
            $objUser = \Auth::user();

            $usersList = User::where('created_by', '=', $objUser->creatorId())
                ->whereNotIn('type', ['super admin', 'company'])->get()->pluck('name', 'id');
            $usersList->prepend('All', '');
            if ($request->month == null) {
                $userdetails = DB::table('login_details')
                    ->join('users', 'login_details.user_id', '=', 'users.id')
                    ->select(DB::raw('login_details.*, users.id as user_id , users.name as user_name , users.email as user_email ,users.type as user_type'))
                    ->where(['login_details.created_by' => \Auth::user()->creatorId()])
                    ->whereMonth('date', date('m'))->whereYear('date', date('Y'));
            } else {
                $userdetails = DB::table('login_details')
                    ->join('users', 'login_details.user_id', '=', 'users.id')
                    ->select(DB::raw('login_details.*, users.id as user_id , users.name as user_name , users.email as user_email ,users.type as user_type'))
                    ->where(['login_details.created_by' => \Auth::user()->creatorId()]);
            }
            if (!empty($request->month)) {
                $userdetails->where('date', '>=', $firstDayofMOnth);
                $userdetails->where('date', '<=', $lastDayofMonth);
            }
            if (!empty($request->employee)) {
                $userdetails->where(['user_id'  => $request->employee]);
            }
            $userdetails = $userdetails->get();

            return view('employee.lastLogin', compact('users', 'usersList', 'userdetails'));
        }

        public function employeeJson(Request $request)
        {
            $employees = Employee::where('branch_id', $request->branch)->get()->mapWithKeys(function ($employee) {
                return [$employee->id => $employee->full_name];
            })->toArray();

            return response()->json($employees);
        }

        public function joiningletterPdf($id)
        {
            $users = \Auth::user();

            $currantLang = $users->currentLanguage();
            $joiningletter = JoiningLetter::where('lang', $currantLang)->where('created_by', \Auth::user()->creatorId())->first();
            
            // Fallback to any available template if none found for current language
            if (!$joiningletter) {
                $joiningletter = JoiningLetter::where('created_by', \Auth::user()->creatorId())->first();
            }
            
            if (!$joiningletter) {
                return redirect()->back()->with('error', __('Joining letter template not found. Please configure it in settings.'));
            }
            
            $date = date('Y-m-d');
            $employees = Employee::where('id', $id)->where('created_by', \Auth::user()->creatorId())->first();
            $settings = \App\Models\Utility::settings();
            $secs = strtotime($settings['company_start_time']) - strtotime("00:00");
            $result = date("H:i", strtotime($settings['company_end_time']) - $secs);
            
            // Get name parts
            $employeeFirstName = !empty($employees->name) ? trim($employees->name) : '';
            $employeeMiddleName = !empty($employees->middle_name) ? trim($employees->middle_name) : '';
            $employeeLastName = !empty($employees->last_name) ? trim($employees->last_name) : '';
            
            // Build full name: name + middle_name + last_name (for "To" section)
            $employeeFullName = trim(implode(' ', array_filter([
                $employeeFirstName,
                $employeeMiddleName,
                $employeeLastName
            ])));
            
            // Format date as DD-MM-YYYY
            $formattedDate = date('d-m-Y', strtotime($date));
            $formattedStartDate = '';
            if (!empty($employees->company_doj)) {
                try {
                    // Handle different date formats (string, Carbon, DateTime)
                    if (is_object($employees->company_doj) && method_exists($employees->company_doj, 'format')) {
                        $formattedStartDate = $employees->company_doj->format('d-m-Y');
                    } else {
                        $formattedStartDate = date('d-m-Y', strtotime($employees->company_doj));
                    }
                } catch (\Exception $e) {
                    $formattedStartDate = date('d-m-Y', strtotime($employees->company_doj));
                }
            }
            
            // Format salary as ₹amount/-
            $formattedSalary = !empty($employees->salary) ? '₹' . number_format((float)$employees->salary, 2) . '/-' : '';
            
            $obj = [
                'date' => $formattedDate,
                'app_name' => env('APP_NAME'),
                'employee_name' => $employeeFullName, // Full name (name + middle_name + last_name) for "To" section
                'employee_first_name' => $employeeFirstName, // First name only for "Dear" section
                'employee_middle_name' => $employeeMiddleName,
                'employee_last_name' => $employeeLastName,
                'address' => !empty($employees->address) ? $employees->address : '',
                'designation' => !empty($employees->designation->name) ? $employees->designation->name : '',
                'start_date' => $formattedStartDate,
                'branch' => !empty($employees->Branch->name) ? $employees->Branch->name : '',
                'start_time' => !empty($settings['company_start_time']) ? $settings['company_start_time'] : '',
                'end_time' => !empty($settings['company_end_time']) ? $settings['company_end_time'] : '',
                'total_hours' => $result,
                'salary' => $formattedSalary,

            ];

            $joiningletter->content = JoiningLetter::replaceVariable($joiningletter->content, $obj);
            return view('employee.template.joiningletterpdf', compact('joiningletter', 'employees'));
        }
        public function joiningletterDoc($id)
        {
            $users = \Auth::user();

            $currantLang = $users->currentLanguage();
            $joiningletter = JoiningLetter::where('lang', $currantLang)->where('created_by', \Auth::user()->creatorId())->first();
            $date = date('Y-m-d');
            $employees = Employee::where('id', $id)->where('created_by', \Auth::user()->creatorId())->first();
            $settings = \App\Models\Utility::settings();
            $secs = strtotime($settings['company_start_time']) - strtotime("00:00");
            $result = date("H:i", strtotime($settings['company_end_time']) - $secs);

            // Get name parts
            $employeeFirstName = !empty($employees->name) ? trim($employees->name) : '';
            $employeeMiddleName = !empty($employees->middle_name) ? trim($employees->middle_name) : '';
            $employeeLastName = !empty($employees->last_name) ? trim($employees->last_name) : '';
            
            // Build full name: name + middle_name + last_name (for "To" section)
            $employeeFullName = trim(implode(' ', array_filter([
                $employeeFirstName,
                $employeeMiddleName,
                $employeeLastName
            ])));
            
            // Format date as DD-MM-YYYY
            $formattedDate = date('d-m-Y', strtotime($date));
            $formattedStartDate = '';
            if (!empty($employees->company_doj)) {
                try {
                    // Handle different date formats (string, Carbon, DateTime)
                    if (is_object($employees->company_doj) && method_exists($employees->company_doj, 'format')) {
                        $formattedStartDate = $employees->company_doj->format('d-m-Y');
                    } else {
                        $formattedStartDate = date('d-m-Y', strtotime($employees->company_doj));
                    }
                } catch (\Exception $e) {
                    $formattedStartDate = date('d-m-Y', strtotime($employees->company_doj));
                }
            }
            
            // Format salary as ₹amount/-
            $formattedSalary = !empty($employees->salary) ? '₹' . number_format((float)$employees->salary, 2) . '/-' : '';

            $obj = [
                'date' => $formattedDate,
                'app_name' => env('APP_NAME'),
                'employee_name' => $employeeFullName, // Full name (name + middle_name + last_name) for "To" section
                'employee_first_name' => $employeeFirstName, // First name only for "Dear" section
                'employee_middle_name' => $employeeMiddleName,
                'employee_last_name' => $employeeLastName,
                'address' => !empty($employees->address) ? $employees->address : '',
                'designation' => !empty($employees->designation->name) ? $employees->designation->name : '',
                'start_date' => $formattedStartDate,
                'branch' => !empty($employees->Branch->name) ? $employees->Branch->name : '',
                'start_time' => !empty($settings['company_start_time']) ? $settings['company_start_time'] : '',
                'end_time' => !empty($settings['company_end_time']) ? $settings['company_end_time'] : '',
                'total_hours' => $result,
                'salary' => $formattedSalary,

            ];
            $joiningletter->content = JoiningLetter::replaceVariable($joiningletter->content, $obj);
            return view('employee.template.joiningletterdocx', compact('joiningletter', 'employees'));
        }
        public function ExpCertificatePdf($id)
        {
            $currantLang = \Cookie::get('LANGUAGE');
            if (!isset($currantLang)) {
                $currantLang = 'en';
            }
            $termination = Termination::where('employee_id', $id)->where('created_by', \Auth::user()->creatorId())->first();
            $experience_certificate = ExperienceCertificate::where('lang', $currantLang)->where('created_by', \Auth::user()->creatorId())->first();
            $date = date('Y-m-d');
            $employees = Employee::where('id', $id)->where('created_by', \Auth::user()->creatorId())->first();
            $settings = \App\Models\Utility::settings();
            $secs = strtotime($settings['company_start_time']) - strtotime("00:00");
            $result = date("H:i", strtotime($settings['company_end_time']) - $secs);
            $date1 = date_create($employees->company_doj);
            $date2 = date_create($employees->termination_date);
            $diff  = date_diff($date1, $date2);
            $duration = $diff->format("%a days");

            // Get resignation details if exists
            $resignation = Resignation::where('employee_id', $id)
                ->where('created_by', \Auth::user()->creatorId())
                ->first();

            if (!empty($termination->termination_date)) {
                // Get name parts (same as joining letter)
                $employeeFirstName = !empty($employees->name) ? trim($employees->name) : '';
                $employeeMiddleName = !empty($employees->middle_name) ? trim($employees->middle_name) : '';
                $employeeLastName = !empty($employees->last_name) ? trim($employees->last_name) : '';
                
                // Build full name: name + middle_name + last_name (for "To" section)
                $employeeFullName = trim(implode(' ', array_filter([
                    $employeeFirstName,
                    $employeeMiddleName,
                    $employeeLastName
                ])));
                
                // Format dates as DD-MM-YYYY
                $formattedDate = date('d-m-Y', strtotime($date));
                $formattedResignationDate = $resignation ? date('d-m-Y', strtotime($resignation->notice_date)) : '';
                $formattedLastWorkingDay = $resignation ? date('d-m-Y', strtotime($resignation->resignation_date)) : '';
                
                $obj = [
                    'date' => $formattedDate,
                    'app_name' => env('APP_NAME'),
                    'employee_name' => $employeeFullName, // Full name (name + middle_name + last_name) for "To" section
                    'employee_first_name' => $employeeFirstName, // First name only for "Dear" section
                    'employee_middle_name' => $employeeMiddleName,
                    'employee_last_name' => $employeeLastName,
                    'payroll' => !empty($employees->salaryType->name) ? $employees->salaryType->name : '',
                    'duration' => $duration,
                    'designation' => !empty($employees->designation->name) ? $employees->designation->name : '',
                    'resignation_date' => $formattedResignationDate,
                    'last_working_day' => $formattedLastWorkingDay,
                ];
            } else {
                return redirect()->back()->with('error', __('Termination date is required.'));
            }

            $experience_certificate->content = ExperienceCertificate::replaceVariable($experience_certificate->content, $obj);
            return view('employee.template.ExpCertificatepdf', compact('experience_certificate', 'employees'));
        }

        public function ExpCertificateDoc($id)
        {
            $currantLang = \Cookie::get('LANGUAGE');
            if (!isset($currantLang)) {
                $currantLang = 'en';
            }
            $termination = Termination::where('employee_id', $id)->where('created_by', \Auth::user()->creatorId())->first();
            $experience_certificate = ExperienceCertificate::where('lang', $currantLang)->where('created_by', \Auth::user()->creatorId())->first();
            $date = date('Y-m-d');
            $employees = Employee::where('id', $id)->where('created_by', \Auth::user()->creatorId())->first();
            $settings = \App\Models\Utility::settings();
            $secs = strtotime($settings['company_start_time']) - strtotime("00:00");
            $result = date("H:i", strtotime($settings['company_end_time']) - $secs);
            $date1 = date_create($employees->company_doj);
            $date2 = date_create($employees->termination_date);
            $diff = date_diff($date1, $date2);
            $duration = $diff->format("%a days");

            // Get resignation details
            $resignation = Resignation::where('employee_id', $id)
                ->where('created_by', \Auth::user()->creatorId())
                ->first();

            if (!empty($termination->termination_date)) {
                // Get name parts (same as joining letter)
                $employeeFirstName = !empty($employees->name) ? trim($employees->name) : '';
                $employeeMiddleName = !empty($employees->middle_name) ? trim($employees->middle_name) : '';
                $employeeLastName = !empty($employees->last_name) ? trim($employees->last_name) : '';
                
                // Build full name: name + middle_name + last_name (for "To" section)
                $employeeFullName = trim(implode(' ', array_filter([
                    $employeeFirstName,
                    $employeeMiddleName,
                    $employeeLastName
                ])));
                
                // Format dates as DD-MM-YYYY
                $formattedDate = date('d-m-Y', strtotime($date));
                $formattedResignationDate = $resignation ? date('d-m-Y', strtotime($resignation->resignation_date)) : '';
                $formattedLastWorkingDay = $resignation ? date('d-m-Y', strtotime($resignation->resignation_date)) : '';
                
                $obj = [
                    'date' => $formattedDate,
                    'app_name' => env('APP_NAME'),
                    'employee_name' => $employeeFullName, // Full name (name + middle_name + last_name) for "To" section
                    'employee_first_name' => $employeeFirstName, // First name only for "Dear" section
                    'employee_middle_name' => $employeeMiddleName,
                    'employee_last_name' => $employeeLastName,
                    'payroll' => !empty($employees->salaryType->name) ? $employees->salaryType->name : '',
                    'duration' => $duration,
                    'designation' => !empty($employees->designation->name) ? $employees->designation->name : '',
                    'resignation_date' => $formattedResignationDate,
                    'last_working_day' => $formattedLastWorkingDay,
                ];
            } else {
                return redirect()->back()->with('error', __('Termination date is required.'));
            }

            $experience_certificate->content = ExperienceCertificate::replaceVariable($experience_certificate->content, $obj);
            return view('employee.template.ExpCertificatedocx', compact('experience_certificate', 'employees'));
        }
        public function NocPdf($id)
        {
            $users = \Auth::user();

            $currantLang = $users->currentLanguage();
            $noc_certificate = NOC::where('lang', $currantLang)->where('created_by', \Auth::user()->creatorId())->first();
            $date = date('Y-m-d');
            $employees = Employee::where('id', $id)->where('created_by', \Auth::user()->creatorId())->first();
            $settings = \App\Models\Utility::settings();
            $secs = strtotime($settings['company_start_time']) - strtotime("00:00");
            $result = date("H:i", strtotime($settings['company_end_time']) - $secs);


            $obj = [
                'date' =>  \Auth::user()->dateFormat($date),
                'employee_name' => $employees->name,
                'designation' => !empty($employees->designation->name) ? $employees->designation->name : '',
                'app_name' => env('APP_NAME'),
            ];

            $noc_certificate->content = NOC::replaceVariable($noc_certificate->content, $obj);
            return view('employee.template.Nocpdf', compact('noc_certificate', 'employees'));
        }
        public function NocDoc($id)
        {
            $users = \Auth::user();

            $currantLang = $users->currentLanguage();
            $noc_certificate = NOC::where('lang', $currantLang)->where('created_by', \Auth::user()->creatorId())->first();
            $date = date('Y-m-d');
            $employees = Employee::where('id', $id)->where('created_by', \Auth::user()->creatorId())->first();
            $settings = \App\Models\Utility::settings();
            $secs = strtotime($settings['company_start_time']) - strtotime("00:00");
            $result = date("H:i", strtotime($settings['company_end_time']) - $secs);


            $obj = [
                'date' =>  \Auth::user()->dateFormat($date),
                'employee_name' => $employees->name,
                'designation' => !empty($employees->designation->name) ? $employees->designation->name : '',
                'app_name' => env('APP_NAME'),
            ];

            $noc_certificate->content = NOC::replaceVariable($noc_certificate->content, $obj);
            return view('employee.template.Nocdocx', compact('noc_certificate', 'employees'));
        }

        public function getdepartment(Request $request)
        {
            if (empty($request->branch_id)) {
                $departments = Department::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id')->toArray();
            } else {
                $departments = Department::where('created_by', '=', \Auth::user()->creatorId())->where('branch_id', $request->branch_id)->get()->pluck('name', 'id')->toArray();
            }
            return response()->json($departments);
        }

        public function json(Request $request)
        {
            if ($request->department_id == 0) {
                $designations = Designation::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id')->toArray();
            }
            $designations = Designation::where('department_id', $request->department_id)->get()->pluck('name', 'id')->toArray();

            return response()->json($designations);
        }

        public function view($id)
        {
            $users = LoginDetail::find($id);
            return view('employee.user_log', compact('users'));
        }

        public function logindestroy($id)
        {
            $employee = LoginDetail::where('user_id', $id)->delete();

            return redirect()->back()->with('success', 'Employee successfully deleted.');
        }

        
    public function employeeIdFormat($number)
    {
        $settings = Utility::settings();
        return $settings["employee_prefix"] . sprintf("%06d", $number);
    }


    private function storeEducationDocument($file, $employeeId, $index)
    {
        $folderPath = storage_path('app/public/uploads/education_images');
        if (!file_exists($folderPath)) {
            mkdir($folderPath, 0777, true);
        }
        
        $filename = 'edu_'.$employeeId.'_'.time().'_'.$index.'.'.$file->getClientOriginalExtension();
        $file->move($folderPath, $filename);
        
        return 'uploads/education_images/'.$filename;
    }

    private function storeEmployeeDocument($file, $employeeId, $documentId)
    {
        $folderPath = storage_path('app/public/uploads/document');
        if (!file_exists($folderPath)) {
            mkdir($folderPath, 0777, true);
        }
        
        $filename = $employeeId.'_'.$documentId.'_'.time().'.'.$file->getClientOriginalExtension();
        $file->move($folderPath, $filename);
        
        return 'uploads/document/'.$filename;
    }

    public function approve($id)
    {
        // Only allow non-employee users (company, hr, director) to approve
        if (\Auth::user()->type !== 'employee' || \Auth::user()->isHR()) {
            $employee = Employee::findOrFail($id);

            $employee->update([
                'approval_status' => 'approved',
                'approved_at' => now(),
                'approved_by' => \Auth::user()->id,
                'rejection_reason' => null,
            ]);

            return redirect()->back()->with('success', __('Employee details have been approved successfully.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function reject(Request $request, $id)
    {
        // Only allow non-employee users (company, hr, director) to reject
        if (\Auth::user()->type !== 'employee' || \Auth::user()->isHR()) {
            $employee = Employee::findOrFail($id);

            $request->validate([
                'rejection_reason' => 'required|string|max:500',
            ]);

            $employee->update([
                'approval_status' => 'rejected',
                'rejection_reason' => $request->rejection_reason,
                'approved_at' => null,
                'approved_by' => null,
            ]);

            return redirect()->back()->with('success', __('Employee details have been rejected.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function requestApproval($id)
    {
        $employee = Employee::findOrFail($id);

        // Only allow the employee themselves to request approval
        if (\Auth::user()->type === 'employee' && $employee->user_id === \Auth::user()->id) {
            $employee->update([
                'approval_status' => 'pending',
                'approved_at' => null,
                'approved_by' => null,
                'rejection_reason' => null,
            ]);

            return redirect()->back()->with('success', __('Approval request has been submitted.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function exportInactiveModal($id)
    {
        if (\Auth::user()->can('Manage Employee')) {
            $employee = Employee::find($id);
            $termination = \App\Models\Termination::where('employee_id', $id)->first();
            return view('employee.export_inactive_modal', compact('employee', 'termination'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function exportInactive(Request $request, $id)
    {
        if (\Auth::user()->can('Manage Employee')) {
            $employee = Employee::find($id);
            $termination = \App\Models\Termination::where('employee_id', $id)->first();
            
            if (!$employee || !$termination) {
                return redirect()->back()->with('error', __('Employee or termination record not found.'));
            }
            
            $exportType = $request->input('export_type');
            $terminationDate = \Carbon\Carbon::parse($termination->termination_date);
            
            if ($exportType == 'up_to_last') {
                $start_date = \Carbon\Carbon::parse($employee->company_doj)->format('Y-m-d');
                $end_date = $terminationDate->format('Y-m-d');
                $fileName = 'attendance_' . $employee->full_name . '_upto_' . $end_date . '.xlsx';
            } else {
                // Month of last working date
                $start_date = $terminationDate->copy()->startOfMonth()->format('Y-m-d');
                $end_date = $terminationDate->format('Y-m-d');
                $fileName = 'attendance_' . $employee->full_name . '_' . $terminationDate->format('M_Y') . '.xlsx';
            }
            
            $attendances = \App\Models\AttendanceEmployee::where('employee_id', $id)
                ->whereBetween('date', [$start_date, $end_date])
                ->orderBy('date', 'asc')
                ->get();
                
            return \Excel::download(new class($employee, $attendances, $start_date, $end_date) implements \Maatwebsite\Excel\Concerns\FromView, \Maatwebsite\Excel\Concerns\WithStyles {
                private $employee;
                private $attendances;
                private $start_date;
                private $end_date;

                public function __construct($employee, $attendances, $start_date, $end_date)
                {
                    $this->employee = $employee;
                    $this->attendances = $attendances;
                    $this->start_date = $start_date;
                    $this->end_date = $end_date;
                }

                public function view(): \Illuminate\View\View
                {
                    return view('employee.export_attendance_excel', [
                        'employee' => $this->employee,
                        'attendances' => $this->attendances,
                        'start_date' => $this->start_date,
                        'end_date' => $this->end_date,
                    ]);
                }
                
                public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet)
                {
                    return [
                        1    => ['font' => ['bold' => true]],
                        2    => ['font' => ['bold' => true]],
                    ];
                }
            }, $fileName);

        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
}