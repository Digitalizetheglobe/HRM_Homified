<?php   
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ToDoList;
use Illuminate\Support\Facades\Auth;

class ToDoListController extends Controller
{
    /**
     * Display a listing of the to-do list items.
     */
    public function index()
    {
        if (Auth::user()->can('Manage ToDoList') || Auth::user()->can('todo.manage.view.own') || Auth::user()->can('todo.manage.view.all')) {
            // Get tasks ordered by whether they are today, completed status, and due date
            $tasks = ToDoList::where('user_id', Auth::user()->id)
                ->orderByRaw('
                    (DATE(expires_at) = CURDATE()) DESC,  -- Show today’s tasks first
                    is_completed ASC,                     -- Show pending tasks before completed tasks
                    expires_at ASC                        -- Order by due date
                ')
                ->get();

            return view('todo.index', compact('tasks'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if (Auth::user()->can('Manage ToDoList') || Auth::user()->can('todo.manage.create.own') || Auth::user()->can('todo.manage.create.all')) {
            return view('todo.create');
        } else {
            return response()->json(['error' => __('Permission denied.')], 403);
        }
    }

    /**
     * Store a new to-do list item in the database.
     */
    public function store(Request $request)
    {
        if (Auth::user()->can('Manage ToDoList') || Auth::user()->can('todo.manage.create.own') || Auth::user()->can('todo.manage.create.all')) {
            // Validate incoming request
            $validatedData = $request->validate([
                'task'        => 'required|string|max:255',
                'priority'    => 'required|in:low,medium,high',
                'expires_at'  => 'nullable|date',
                'is_completed'=> 'required|boolean',
            ]);

            // Create new ToDo List item and associate with the logged-in user
            ToDoList::create([
                'user_id'     => Auth::id(),  // Logged-in user's ID
                'task'        => $validatedData['task'],
                'priority'    => $validatedData['priority'],
                'expires_at'  => $validatedData['expires_at'],
                'is_completed'=> $validatedData['is_completed'],
            ]);

            return redirect()->route('todo.index')->with('success', __('To-Do item created successfully.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Show the form for editing a to-do list item.
     */
    public function edit($id)
    {
        // Find the task
        $todo = ToDoList::findOrFail($id);

        // Check permission
        $canEdit = false;
        if (Auth::user()->can('todo.manage.edit.all')) {
            $canEdit = true;
        } elseif ($todo->user_id === Auth::user()->id && (Auth::user()->can('todo.manage.edit.own') || Auth::user()->can('Manage ToDoList'))) {
            $canEdit = true;
        }

        if ($canEdit) {
            return view('todo.edit', compact('todo'));
        } else {
            return response()->json(['error' => __('You do not have permission to edit this task.')], 403);
        }
    }

    /**
     * Update the specified to-do list item in the database.
     */
    public function update(Request $request, $id)
    {
        $todo = ToDoList::findOrFail($id);
    
        // Check permission
        $canUpdate = false;
        if (Auth::user()->can('todo.manage.edit.all')) {
            $canUpdate = true;
        } elseif ($todo->user_id === Auth::user()->id && (Auth::user()->can('todo.manage.edit.own') || Auth::user()->can('Manage ToDoList'))) {
            $canUpdate = true;
        }

        if ($canUpdate) {
            // Validation
            $request->validate([
                'task' => 'required|string|max:255',
                'priority' => 'nullable|in:high,medium,low',
                'expires_at' => 'nullable|date',
                'is_completed' => 'nullable|boolean',
            ]);
    
            // Update task
            $todo->update($request->only(['task', 'priority', 'expires_at', 'is_completed']));
    
            return redirect()->route('todo.index')->with('success', __('Task updated successfully.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Remove the specified to-do list item from the database.
     */
    public function destroy(ToDoList $todo)
    {
        // Check permission
        $canDelete = false;
        if (Auth::user()->can('todo.manage.delete.all')) {
            $canDelete = true;
        } elseif ($todo->user_id === Auth::user()->id && (Auth::user()->can('todo.manage.delete.own') || Auth::user()->can('Manage ToDoList'))) {
            $canDelete = true;
        }

        if ($canDelete) {
            $todo->delete();
            return redirect()->route('todo.index')->with('success', __('Task successfully deleted.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
}
