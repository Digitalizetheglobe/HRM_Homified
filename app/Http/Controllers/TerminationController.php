<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Mail\TerminationSend;
use App\Models\Resignation;
use App\Models\Termination;
use App\Models\TerminationType;
use App\Models\Utility;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class TerminationController extends Controller
{
    public function index(Request $request)
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('exit.termination.view.all') || \Auth::user()->can('exit.termination.view.own') || \Auth::user()->type == 'employee')
        {
            if(Auth::user()->type == 'employee' && (!Auth::user()->can('exit.termination.view.all') || $request->has('own')))
            {
                $emp          = Employee::where('user_id', '=', \Auth::user()->id)->first();
                $terminations = Termination::where('created_by', '=', \Auth::user()->creatorId())->where('employee_id', '=', $emp->id)->orderBy('created_at', 'desc')->orderBy('id', 'desc')->get();
            }
            else
            {
                $terminations = Termination::where('created_by', '=', \Auth::user()->creatorId())->with(['employee', 'terminationType'])->orderBy('created_at', 'desc')->orderBy('id', 'desc')->get();
            }

            return view('termination.index', compact('terminations'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('exit.termination.create.all'))
        {
            $approvedResignedEmployeeIds = Resignation::where('created_by', \Auth::user()->creatorId())
                ->where('status', 'approved')
                ->pluck('employee_id')
                ->unique()
                ->filter()
                ->values();

            // Get employee IDs that already have terminations
            $terminatedEmployeeIds = Termination::where('created_by', \Auth::user()->creatorId())
                ->pluck('employee_id')
                ->unique()
                ->filter()
                ->values()
                ->toArray();

            // Get employees with approved resignations but exclude those who already have terminations
            $employees = Employee::where('created_by', \Auth::user()->creatorId())
                ->whereIn('id', $approvedResignedEmployeeIds)
                ->whereNotIn('id', $terminatedEmployeeIds)
                ->get()
                ->mapWithKeys(function ($employee) {
                    return [$employee->id => $employee->full_name];
                });
            $terminationtypes = TerminationType::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');

            return view('termination.create', compact('employees', 'terminationtypes'));
        }
        else
        {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function store(Request $request)
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('exit.termination.create.all'))
        {

            $validator = \Validator::make(
                $request->all(), [
                                   'employee_id' => 'required',
                                   'termination_type' => 'required',
                                   'notice_date' => 'nullable|date',
                                   'termination_date' => 'required|date',
                                   'description' => 'required',
                               ]
            );

            if($validator->fails())
            {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $noticeDate = $request->notice_date;
            if(empty($noticeDate))
            {
                $noticeDate = Resignation::where('created_by', \Auth::user()->creatorId())
                    ->where('employee_id', $request->employee_id)
                    ->where('status', 'approved')
                    ->orderByDesc('approved_at')
                    ->orderByDesc('id')
                    ->value('notice_date');
            }

            if(empty($noticeDate))
            {
                $noticeDate = now()->toDateString();
            }

            if(Carbon::parse($request->termination_date)->lt(Carbon::parse($noticeDate)))
            {
                return redirect()->back()->with('error', __('Termination date must be after or equal to notice date.'));
            }

            // Check if employee already has a termination
            $existingTermination = Termination::where('created_by', \Auth::user()->creatorId())
                ->where('employee_id', $request->employee_id)
                ->first();

            if($existingTermination)
            {
                return redirect()->back()->with('error', __('This employee already has a termination record.'));
            }

            $termination                   = new Termination();
            $termination->employee_id      = $request->employee_id;
            $termination->termination_type = $request->termination_type;
            $termination->notice_date      = $noticeDate;
            $termination->termination_date = $request->termination_date;
            $termination->description      = $request->description;
            $termination->created_by       = \Auth::user()->creatorId();
            $termination->save();

            $employee = Employee::find($request->employee_id);
            if ($employee && $employee->user) {
                // You might want to immediately disable the user or wait until termination date
                // Option 1: Disable immediately
                // $employee->user->is_active = 0;
                // $employee->user->save();
                
                // Option 2: Let the middleware handle it based on termination date
            }


            $setings = Utility::settings();
            if($setings['employee_termination'] == 1)
            {
                $employee           = Employee::find($termination->employee_id);

            $uArr = [
                'employee_termination_name'=>$employee->full_name, 
                'notice_date'=>$noticeDate,
                'termination_date'=>$request->termination_date, 
                'termination_type'=>$request->termination_type, 
             ];
          $resp = Utility::sendEmailTemplate('employee_termination', [$employee->email], $uArr);
           return redirect()->route('termination.index')->with('success', __('Termination  successfully created.'). ((!empty($resp) && $resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));
            }

            return redirect()->route('termination.index')->with('success', __('Termination  successfully created.'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function show(Termination $termination)
    {
        return redirect()->route('termination.index');
    }

    public function edit(Termination $termination)
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('exit.termination.edit.all'))
        {
            if($termination->created_by == \Auth::user()->creatorId())
            {
                // Get employee IDs that already have terminations (excluding current termination)
                $terminatedEmployeeIds = Termination::where('created_by', \Auth::user()->creatorId())
                    ->where('id', '!=', $termination->id)
                    ->pluck('employee_id')
                    ->unique()
                    ->filter()
                    ->values()
                    ->toArray();

                // Get employees excluding those who already have terminations, but include current employee
                $employees = Employee::where('created_by', \Auth::user()->creatorId())
                    ->where(function($query) use ($terminatedEmployeeIds, $termination) {
                        $query->whereNotIn('id', $terminatedEmployeeIds)
                              ->orWhere('id', $termination->employee_id);
                    })
                    ->get()
                    ->pluck('name', 'id');
                    
                $terminationtypes = TerminationType::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');

                return view('termination.edit', compact('termination', 'employees', 'terminationtypes'));
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

    public function update(Request $request, Termination $termination)
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('exit.termination.edit.all'))
        {
            if($termination->created_by == \Auth::user()->creatorId())
            {
                $validator = \Validator::make(
                    $request->all(), [
                                       'employee_id' => 'required',
                                       'termination_type' => 'required',
                                       'notice_date' => 'required',
                                       'termination_date' => 'required',
                                   ]
                );

                if($validator->fails())
                {
                    $messages = $validator->getMessageBag();

                    return redirect()->back()->with('error', $messages->first());
                }

                $newEmployeeId = $request->employee_id;
                
                // Check if the new employee already has a termination (excluding current one)
                $existingTermination = Termination::where('created_by', \Auth::user()->creatorId())
                    ->where('employee_id', $newEmployeeId)
                    ->where('id', '!=', $termination->id)
                    ->first();

                if($existingTermination)
                {
                    return redirect()->back()->with('error', __('This employee already has a termination record.'));
                }

                $termination->employee_id      = $newEmployeeId;
                $termination->termination_type = $request->termination_type;
                $termination->notice_date      = $request->notice_date;
                $termination->termination_date = $request->termination_date;
                $termination->description      = $request->description;
                $termination->save();

                $employee = Employee::find($request->employee_id);
                if ($employee && $employee->user) {
                    // Same logic as above
                }

                return redirect()->route('termination.index')->with('success', __('Termination successfully updated.'));
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

    public function destroy(Termination $termination)
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('exit.termination.delete.all'))
        {
            if($termination->created_by == \Auth::user()->creatorId())
            {
                $termination->delete();

                return redirect()->route('termination.index')->with('success', __('Termination successfully deleted.'));
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

    public function description($id)
    {
        $termination = Termination::find($id);

        return view('termination.description', compact('termination'));
    }
}
