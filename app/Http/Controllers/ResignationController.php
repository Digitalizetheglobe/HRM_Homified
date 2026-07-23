<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Resignation;
use App\Models\User;
use App\Models\Utility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;


class ResignationController extends Controller
{
    public function index(Request $request)
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('exit.resignation.view.all') || \Auth::user()->can('exit.resignation.view.own') || \Auth::user()->type == 'employee') {
            if(Auth::user()->type == 'employee' && (!\Auth::user()->can('exit.resignation.view.all') || $request->has('own'))) {
                $emp = Employee::where('user_id', \Auth::user()->id)->first();
                $resignations = Resignation::where('created_by', \Auth::user()->creatorId())
                    ->where('employee_id', $emp->id)
                    ->get();
            } else {
                $resignations = Resignation::where('created_by', \Auth::user()->creatorId())
                    ->with(['employee', 'approvedBy'])
                    ->get();
            }

            return view('resignation.index', compact('resignations'));
        }
        return redirect()->back()->with('error', __('Permission denied.'));
    }

    public function create()
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('exit.resignation.create.all') || \Auth::user()->can('exit.resignation.create.own') || \Auth::user()->type == 'employee')
        {
            // Get employee IDs that already have resignations
            $resignedEmployeeIds = Resignation::where('created_by', \Auth::user()->creatorId())
                ->pluck('employee_id')
                ->unique()
                ->filter()
                ->values()
                ->toArray();

            // Get employees excluding those who already have resignations
            $employees = Employee::where('created_by', \Auth::user()->creatorId())
                ->whereNotIn('id', $resignedEmployeeIds)
                ->get()
                ->mapWithKeys(function ($employee) {
                    return [$employee->id => $employee->full_name];
                });

            return view('resignation.create', compact('employees'));
        }
        else
        {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function store(Request $request)
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('exit.resignation.create.all') || \Auth::user()->can('exit.resignation.create.own') || \Auth::user()->type == 'employee')
        {

            $validator = \Validator::make(
                $request->all(), [

                                   'notice_date' => 'required',
                                   'resignation_date' => 'required|after_or_equal:notice_date',
                               ]
            );

            if($validator->fails())
            {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $resignation = new Resignation();
            $user        = \Auth::user();
            if($user->type == 'employee' && (!$user->can('exit.resignation.create.all') || empty($request->employee_id)))
            {
                $employee                 = Employee::where('user_id', $user->id)->first();
                $resignation->employee_id = $employee->id;
            }
            else
            {
                $resignation->employee_id = $request->employee_id;
            }

            // Check if employee already has a resignation
            $existingResignation = Resignation::where('created_by', \Auth::user()->creatorId())
                ->where('employee_id', $resignation->employee_id)
                ->first();

            if($existingResignation)
            {
                return redirect()->back()->with('error', __('This employee already has a resignation submitted.'));
            }
            $resignation->notice_date      = $request->notice_date;
            $resignation->resignation_date = $request->resignation_date;
            $resignation->description      = $request->description ;
            $resignation->created_by       = \Auth::user()->creatorId();

            $resignation->save();

            $setings = Utility::settings();
            if($setings['employee_resignation'] == 1)
            {
                $employee           = Employee::find($resignation->employee_id);
                 $uArr = [
                'assign_user'=>$employee->full_name,
                'resignation_date'  =>$request->notice_date,
                'notice_date' =>$request->resignation_date,
             ];

             $resp = Utility::sendEmailTemplate('employee_resignation', [$employee->email], $uArr);
             return redirect()->route('resignation.index')->with('success', __('Resignation  successfully created.'). ((!empty($resp) && $resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));
            
            
                $user           = User::find($employee->created_by);
                 $uArr = [
                'assign_user'=>$user->name,
                'resignation_date'  =>$request->notice_date,
                'notice_date' =>$request->resignation_date,
             ];

                $resp = Utility::sendEmailTemplate('employee_resignation', [$user->email], $uArr);
                 return redirect()->route('resignation.index')->with('success', __('Resignation  successfully created.'). ((!empty($resp) && $resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));
            
            }

            return redirect()->route('resignation.index')->with('success', __('Resignation  successfully created.'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function show(Resignation $resignation)
    {
        return redirect()->route('resignation.index');
    }

    public function edit(Resignation $resignation)
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('exit.resignation.edit.all'))
        {
            if($resignation->created_by == \Auth::user()->creatorId())
            {
                // Get employee IDs that already have resignations (excluding current resignation)
                $resignedEmployeeIds = Resignation::where('created_by', \Auth::user()->creatorId())
                    ->where('id', '!=', $resignation->id)
                    ->pluck('employee_id')
                    ->unique()
                    ->filter()
                    ->values()
                    ->toArray();

                // Get employees excluding those who already have resignations, but include current employee
                $employees = Employee::where('created_by', \Auth::user()->creatorId())
                    ->where(function($query) use ($resignedEmployeeIds, $resignation) {
                        $query->whereNotIn('id', $resignedEmployeeIds)
                              ->orWhere('id', $resignation->employee_id);
                    })
                    ->get()
                    ->mapWithKeys(function ($employee) {
                        return [$employee->id => $employee->full_name];
                    });

                return view('resignation.edit', compact('resignation', 'employees'));
            }
            else
            {
                return response()->json(['error' => __('Permission denied.')], 401);
            }
        }
        else
        {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function update(Request $request, Resignation $resignation)
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('exit.resignation.edit.all'))
        {
            if($resignation->created_by == \Auth::user()->creatorId())
            {
                $validator = \Validator::make(
                    $request->all(), [

                                       'notice_date' => 'required',
                                       'resignation_date' => 'required',
                                   ]
                );

                if($validator->fails())
                {
                    $messages = $validator->getMessageBag();

                    return redirect()->back()->with('error', $messages->first());
                }

                if(\Auth::user()->type != 'employee')
                {
                    $newEmployeeId = $request->employee_id;
                    
                    // Check if the new employee already has a resignation (excluding current one)
                    $existingResignation = Resignation::where('created_by', \Auth::user()->creatorId())
                        ->where('employee_id', $newEmployeeId)
                        ->where('id', '!=', $resignation->id)
                        ->first();

                    if($existingResignation)
                    {
                        return redirect()->back()->with('error', __('This employee already has a resignation submitted.'));
                    }
                    
                    $resignation->employee_id = $newEmployeeId;
                }


                $resignation->notice_date      = $request->notice_date;
                $resignation->resignation_date = $request->resignation_date;
                $resignation->description      = $request->description;

                $resignation->save();

                return redirect()->route('resignation.index')->with('success', __('Resignation successfully updated.'));
            }
            else
            {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function destroy(Resignation $resignation)
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('exit.resignation.delete.all'))
        {
            if($resignation->created_by == \Auth::user()->creatorId())
            {
                $resignation->delete();

                return redirect()->route('resignation.index')->with('success', __('Resignation successfully deleted.'));
            }
            else
            {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function review($id)
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('exit.resignation.show.all') || \Auth::user()->can('exit.resignation.show.own')) {
            $resignation = Resignation::with(['employee'])->findOrFail($id);
            
            if (\Auth::user()->type != 'company' && !\Auth::user()->can('exit.resignation.show.all')) {
                $employee = \Auth::user()->employee;
                if (!$employee || $resignation->employee_id != $employee->id) {
                    return redirect()->back()->with('error', __('Permission denied.'));
                }
            }
            
            return view('resignation.review', compact('resignation'));
        }
        return redirect()->back()->with('error', __('Permission denied.'));
    }

    public function description($id)
    {
        $resignation = Resignation::find($id);
        return view('resignation.description', compact('resignation'));
    }

    public function approve(Request $request, $id)
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('exit.resignation.approve.all')) {
            $resignation = Resignation::findOrFail($id);
            
            $validator = \Validator::make($request->all(), [
                'notice_date' => 'required',
                'resignation_date' => 'required|after_or_equal:notice_date',
            ]);

            if($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            // Update dates if changed
            $resignation->update([
                'notice_date' => $request->notice_date,
                'resignation_date' => $request->resignation_date,
                'status' => 'approved',
                'approved_by' => \Auth::id(),
                'approved_at' => now(),
            ]);

            // NOTE: Per requirement, do NOT send any email on approval.
            return redirect()->route('resignation.index')
                ->with('success', __('Resignation approved successfully.'));
        }
        return redirect()->back()->with('error', __('Permission denied.'));
    }
}
