<?php

namespace App\Jobs;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Filament\Notifications\Notification;

class SendTaskReminderNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
         \Log::info('📢 SendTaskReminderNotifications job started!');

        $now = now();

        $tasks = Task::whereNotNull('due_date_time')
            ->where('due_date_time', '<=', $now)
            ->whereNull('reminder_sent_at')
            ->get();

        \Log::info('📌 Tasks count found: ' . $tasks->count());

        foreach ($tasks as $task) {
            $user = User::find($task->user_id);

            \Log::info("🔁 Checking task ID {$task->id} for user: " . ($user?->id ?? 'null'));

            if ($user) {
                Notification::make()
                    ->title('📌 Task Reminder')

                    ->body(strip_tags($task->description))
                    ->success()
                    ->sendToDatabase($user);

                $task->forceFill([
                        'reminder_sent_at' => $now
                    ])->save();      
                    
                    
                \Log::info("✅ Notification sent for Task ID {$task->id} to user {$user->id}");
            } else {
                \Log::warning("⚠️ No user found for Task ID {$task->id}");
            }
        }
    }
}
