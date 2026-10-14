<?php

namespace App\Http\Controllers;

use App\Models\PunchLocation;
use Illuminate\Http\Request;

class PunchLocationController extends Controller
{
    public function index()
    {
        $user = \Auth::user();
        if ($user->can('Manage Punch Location') || $user->can('setup.hrm.view.company') || $user->hasCompanyAccess() || $user->isHR()) {
            $locations = PunchLocation::where('created_by', $user->creatorId())
                ->withCount('employees')
                ->orderBy('name')
                ->get();

            return view('punchlocation.index', compact('locations'));
        }

        return redirect()->back()->with('error', __('Permission denied.'));
    }

    public function create()
    {
        if ($this->canManage()) {
            return view('punchlocation.create');
        }

        return response()->json(['error' => __('Permission denied.')], 401);
    }

    public function store(Request $request)
    {
        if (!$this->canManage()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $validator = \Validator::make($request->all(), $this->rules());
        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->getMessageBag()->first());
        }

        PunchLocation::create([
            'name' => $request->name,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'radius_meters' => $request->radius_meters ?: 20,
            'created_by' => \Auth::user()->creatorId(),
        ]);

        return redirect()->route('punchlocation.index')->with('success', __('Punch location successfully created.'));
    }

    public function show(PunchLocation $punchlocation)
    {
        return redirect()->route('punchlocation.index');
    }

    public function edit(PunchLocation $punchlocation)
    {
        if (!$this->canManage() || $punchlocation->created_by != \Auth::user()->creatorId()) {
            return response()->json(['error' => __('Permission denied.')], 401);
        }

        return view('punchlocation.edit', compact('punchlocation'));
    }

    public function update(Request $request, PunchLocation $punchlocation)
    {
        if (!$this->canManage() || $punchlocation->created_by != \Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $validator = \Validator::make($request->all(), $this->rules());
        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->getMessageBag()->first());
        }

        $punchlocation->name = $request->name;
        $punchlocation->latitude = $request->latitude;
        $punchlocation->longitude = $request->longitude;
        $punchlocation->radius_meters = $request->radius_meters ?: 20;
        $punchlocation->save();

        return redirect()->route('punchlocation.index')->with('success', __('Punch location successfully updated.'));
    }

    public function destroy(PunchLocation $punchlocation)
    {
        if (!$this->canManage() || $punchlocation->created_by != \Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        if ($punchlocation->employees()->exists()) {
            return redirect()->route('punchlocation.index')->with('error', __('This location is assigned to employees. Remove it from those employees first.'));
        }

        $punchlocation->delete();

        return redirect()->route('punchlocation.index')->with('success', __('Punch location successfully deleted.'));
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius_meters' => 'nullable|integer|min:5|max:500',
        ];
    }

    protected function canManage(): bool
    {
        $user = \Auth::user();

        return $user->can('Create Punch Location')
            || $user->can('Edit Punch Location')
            || $user->can('Manage Punch Location')
            || $user->can('setup.hrm.view.all')
            || $user->hasCompanyAccess()
            || $user->isHR();
    }
}
