<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notice;
use Illuminate\Support\Facades\Auth;

class NoticeController extends Controller
{
    public function index()
    {
        if (\Auth::user()->type == 'company' || \Auth::user()->can('notice.manage.view.own') || \Auth::user()->can('notice.manage.view.all') || \Auth::user()->can('Manage Notice')) {
            $notices = Notice::latest()->get();
            return view('notice.index', compact('notices'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if (\Auth::user()->type == 'company' || \Auth::user()->can('notice.manage.create.all') || \Auth::user()->can('Create Employee')) {
            return view('notice.create');
        } else {
            return response()->json(['error' => __('Permission Denied.')], 401);
        }
    }

    public function store(Request $request)
    {
        if (\Auth::user()->type == 'company' || \Auth::user()->can('notice.manage.create.all') || \Auth::user()->can('Create Employee')) {
            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required',
                'notice_startdate' => 'required|date|after_or_equal:today',
                'notice_enddate' => 'required|date|after_or_equal:notice_startdate',
            ]);

            Notice::create([
                'title' => $request->title,
                'description' => $request->description,
                'notice_startdate' => $request->notice_startdate,
                'notice_enddate' => $request->notice_enddate,
                'created_by' => Auth::id(),
            ]);

            return redirect()->route('notices.index')->with('success', 'Notice created successfully.');
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function edit(Notice $notice)
    {
        if (\Auth::user()->type == 'company' || \Auth::user()->can('notice.manage.edit.all') || \Auth::user()->can('Edit Meeting')) {
            return view('notice.edit', compact('notice'));
        } else {
            return response()->json(['error' => __('Permission Denied.')], 401);
        }
    }

    public function update(Request $request, Notice $notice)
    {
        if (\Auth::user()->type == 'company' || \Auth::user()->can('notice.manage.edit.all') || \Auth::user()->can('Edit Meeting')) {
            // Validate the request
            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'notice_startdate' => 'required|date',
                'notice_enddate' => 'required|date|after_or_equal:notice_startdate',
            ]);

            // Update the notice
            $data = [
                'title' => $request->title,
                'description' => $request->description,
                'notice_startdate' => \Carbon\Carbon::parse($request->notice_startdate)->format('Y-m-d'),
                'notice_enddate' => \Carbon\Carbon::parse($request->notice_enddate)->format('Y-m-d'),
            ];

            $notice->update($data);

            return redirect()->route('notices.index')->with('success', 'Notice updated successfully.');
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function destroy(Notice $notice)
    {
        if (\Auth::user()->type == 'company' || \Auth::user()->can('notice.manage.delete.all') || \Auth::user()->can('Delete Meeting')) {
            $notice->delete();
            return redirect()->route('notices.index')->with('success', __('Notice deleted successfully.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
}
