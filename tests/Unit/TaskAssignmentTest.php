<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Task;
use App\Policies\TaskPolicy;

class TaskAssignmentTest extends TestCase
{
    public function test_manager_can_assign_to_staff()
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $staff = User::factory()->create(['role' => 'staff']);
        $policy = new TaskPolicy();

        $this->assertTrue($policy->create($manager, $staff));
    }

    public function test_manager_cannot_assign_to_manager()
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $otherManager = User::factory()->create(['role' => 'manager']);
        $policy = new TaskPolicy();

        $this->assertFalse($policy->create($manager, $otherManager));
    }
}