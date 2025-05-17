<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str; // Added import for Str

class UserController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        if (!$user->status) {
            return response()->json(['error' => 'User is inactive'], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json(['token' => $token, 'user' => $user]);
    }

    public function index()
    {
        return User::all();
    }

    public function store(Request $request)
    {
        try {
            $authUser = $request->user();
            if (!$authUser) {
                Log::error('No authenticated user found');
                return response()->json(['error' => 'Unauthenticated'], 401);
            }
            Log::info('Starting user creation', [
                'user_id' => $authUser->id,
                'request_data' => $request->all(),
            ]);

            $this->authorize('create', User::class);
            Log::info('Authorization passed');

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:6',
                'role' => 'required|in:admin,manager,staff',
            ]);
            Log::info('Validation passed', ['validated_data' => $validated]);

            $user = User::create([
                'id' => Str::uuid()->toString(),
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']), // Use Hash::make instead of bcrypt
                'role' => $validated['role'],
                'status' => true,
            ]);
            Log::info('User created', ['user' => $user->toArray()]);

            ActivityLog::create([
                'user_id' => $authUser->id,
                'action' => 'create_user',
                'description' => "Created user: {$user->id}",
            ]);
            Log::info('Activity log created');

            return response()->json($user, 201);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            Log::error('Authorization failed', [
                'user_id' => $authUser->id ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'You are not authorized to create a user'], 403);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed', [
                'errors' => $e->errors(),
                'request_data' => $request->all(),
            ]);
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error', [
                'error' => $e->getMessage(),
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
                'request_data' => $request->all(),
            ]);
            return response()->json(['error' => 'Database error: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            Log::error('Failed to create user', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);
            return response()->json(['error' => 'Failed to create user: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            Log::info('Starting update user process', ['id' => $id, 'request_data' => $request->all()]);

            $authUser = $request->user();
            Log::info('Authenticated user', ['user_id' => $authUser ? $authUser->id : null]);

            Log::info('Finding user to update', ['id' => $id]);
            $user = User::findOrFail($id);
            Log::info('User found', ['id' => $user->id, 'name' => $user->name, 'email' => $user->email]);

            Log::info('Validating request data', ['data' => $request->all()]);
            $validatedData = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $id . ',id',
                'password' => 'sometimes|min:6',
                'role' => 'sometimes|in:admin,manager,staff',
                'status' => 'sometimes|boolean',
            ]);
            Log::info('Validation passed', ['validated_data' => $validatedData]);

            if ($request->has('name')) $user->name = $request->name;
            if ($request->has('email')) $user->email = $request->email;
            if ($request->has('password')) $user->password = Hash::make($request->password);
            if ($request->has('role')) $user->role = $request->role;
            if ($request->has('status')) $user->status = $request->status;

            Log::info('Saving user changes', ['id' => $user->id, 'data' => $user->getAttributes()]);
            $user->save();
            Log::info('User changes saved', ['id' => $user->id, 'data' => $user->getAttributes()]);

            Log::info('Creating activity log', ['user_id' => $authUser ? $authUser->id : null]);
            ActivityLog::create([
                'user_id' => $authUser ? $authUser->id : null,
                'action' => 'update_user',
                'description' => "Updated user: {$user->id}",
            ]);
            Log::info('Activity log created');

            return response()->json($user, 200);
        } catch (\Exception $e) {
            Log::error('Failed to update user', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
                'user_data' => $user ?? 'User not found'
            ]);
            return response()->json(['error' => 'Failed to update user: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);
            $user->delete();

            ActivityLog::create([
                'user_id' => request()->user()->id ?? null,
                'action' => 'delete_user',
                'description' => "Deleted user: {$id}",
            ]);

            return response()->json(['message' => 'User deleted'], 200);
        } catch (\Exception $e) {
            Log::error('Failed to delete user', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete user: ' . $e->getMessage()], 500);
        }
    }
}
