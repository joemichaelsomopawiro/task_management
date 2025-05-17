<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Log;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if ($user->role === 'admin') {
            return Task::with(['assignee', 'creator'])->get();
        }
        if ($user->role === 'manager') {
            return Task::where('created_by', $user->id)
                ->orWhereHas('assignee', fn($query) => $query->where('role', 'staff'))
                ->with(['assignee', 'creator'])
                ->get();
        }
        return Task::where('assigned_to', $user->id)
            ->orWhere('created_by', $user->id)
            ->with(['assignee', 'creator'])
            ->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
            'assigned_to' => 'required|uuid|exists:users,id',
            'due_date' => 'required|date',
        ]);

        $assignedTo = User::find($request->assigned_to);
        $this->authorize('create', [Task::class, $assignedTo]);

        $task = Task::create([
            'title' => $request->title,
            'description' => $request->description,
            'assigned_to' => $request->assigned_to,
            'due_date' => $request->due_date,
            'created_by' => $request->user()->id,
        ]);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'create_task',
            'description' => "Created task: {$task->id}",
        ]);

        return response()->json($task, 201);
    }

     public function update(Request $request, Task $task)
    {
        try {
            Log::info('Starting task update', [
                'id' => $task->id,
                'user_id' => $request->user()->id,
                'request_data' => $request->all(),
            ]);

            $this->authorize('update', $task);
            Log::info('Authorization passed');

            $validated = $request->validate([
                'title' => 'sometimes|string|max:255',
                'description' => 'sometimes|string',
                'status' => 'sometimes|in:pending,in_progress,done',
                'due_date' => 'sometimes|date',
                'assigned_to' => 'sometimes|uuid|exists:users,id',
            ]);
            Log::info('Validation passed', ['validated_data' => $validated]);

            $updateData = $request->only(['title', 'description', 'status', 'due_date', 'assigned_to']);
            Log::info('Update data', ['data' => $updateData]);

            $task->update($updateData);
            Log::info('Task updated', ['task' => $task->toArray()]);

            ActivityLog::create([
                'user_id' => $request->user()->id,
                'action' => 'update_task',
                'description' => "Updated task: {$task->id}",
            ]);
            Log::info('Activity log created');

            return response()->json($task);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            Log::error('Authorization failed', [
                'id' => $task->id ?? 'unknown',
                'user_id' => $request->user()->id ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'You are not authorized to update this task'], 403);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed', [
                'id' => $task->id ?? 'unknown',
                'errors' => $e->errors(),
                'request_data' => $request->all(),
            ]);
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error', [
                'id' => $task->id ?? 'unknown',
                'error' => $e->getMessage(),
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
                'request_data' => $request->all(),
            ]);
            return response()->json(['error' => 'Database error: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            Log::error('Failed to update task', [
                'id' => $task->id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);
            return response()->json(['error' => 'Failed to update task: ' . $e->getMessage()], 500);
        }
    }
    public function destroy(Request $request, $id)
    {
        $task = Task::findOrFail($id);
        $this->authorize('delete', $task);

        $task->delete();

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'delete_task',
            'description' => "Deleted task: {$id}",
        ]);

        return response()->json(['message' => 'Task deleted']);
    }
}