<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TaskPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return true; // All roles can view tasks, filtered by role logic in controller
    }

    public function create(User $user, User $assignedTo = null)
    {
        if ($user->role === 'staff') {
            return $assignedTo && $assignedTo->id === $user->id;
        }
        if ($user->role === 'manager') {
            return $assignedTo && $assignedTo->role === 'staff';
        }
        return $user->role === 'admin';
    }

    public function update(User $user, Task $task)
    {
        if ($user->role === 'admin') return true;
        if ($user->role === 'manager') return $task->creator->id === $user->id;
        return $task->assigned_to === $user->id;
    }

    public function delete(User $user, Task $task)
    {
        return $user->role === 'admin' || $task->created_by === $user->id;
    }
}