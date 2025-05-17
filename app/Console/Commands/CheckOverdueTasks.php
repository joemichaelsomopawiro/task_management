<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Task;
use App\Models\ActivityLog;
use Carbon\Carbon;

class CheckOverdueTasks extends Command
{
    protected $signature = 'tasks:check-overdue';
    protected $description = 'Check for overdue tasks and log them';

    public function handle()
    {
        $overdueTasks = Task::where('due_date', '<', Carbon::today())
            ->where('status', '!=', 'done')
            ->get();

        foreach ($overdueTasks as $task) {
            ActivityLog::create([
                'user_id' => $task->created_by,
                'action' => 'task_overdue',
                'description' => "Task overdue: {$task->id}",
            ]);
            $this->info("Logged overdue task: {$task->id}");
        }
    }
}