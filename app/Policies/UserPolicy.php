<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Log;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        Log::info('Checking viewAny policy', ['user' => $user->id, 'role' => $user->role]);
        return $user->role === 'admin' || $user->role === 'manager';
    }

    public function create(User $user)
    {
        Log::info('Checking create policy', ['user' => $user->id, 'role' => $user->role]);
        return $user->role === 'admin';
    }

    public function update(User $user, User $model)
    {
        Log::info('Checking update policy', [
            'user_id' => $user->id,
            'user_role' => $user->role,
            'model_id' => $model->id,
        ]);
        return $user->role === 'admin'; // Hanya admin yang bisa edit user lain
    }

    public function delete(User $user, User $model)
    {
        Log::info('Checking delete policy', [
            'user_id' => $user->id,
            'user_role' => $user->role,
            'model_id' => $model->id,
        ]);
        return $user->role === 'admin'; // Hanya admin yang bisa hapus user lain
    }
}