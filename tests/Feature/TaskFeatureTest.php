<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TaskFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_task()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $assignedTo = User::factory()->create(['role' => 'staff']);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/tasks', [
            'title' => 'Test Task',
            'description' => 'Test Description',
            'assigned_to' => $assignedTo->id,
            'due_date' => now()->addDay()->toDateString(),
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('tasks', ['title' => 'Test Task']);
    }

    public function test_unauthenticated_user_cannot_access_tasks()
    {
        $response = $this->getJson('/api/tasks');
        $response->assertStatus(401);
    }
}