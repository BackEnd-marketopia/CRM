<?php

namespace App\Jobs;

use App\Models\Note;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class SendNoteReminderNotifications implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        \Log::info('📢 SendNoteReminderNotifications job started!');

        $now = now();

        $notes = Note::whereNotNull('due_date_time')
            ->where('due_date_time', '<=', $now)
            ->whereNull('reminder_sent_at')
            ->get();

        \Log::info('📌 Notes count found: ' . $notes->count());

        foreach ($notes as $note) {
            $user = User::find($note->created_by);

            \Log::info("🔁 Checking Note ID {$note->id} for user: " . ($user?->id ?? 'null'));

            if ($user) {
                Notification::make()
                    ->title('📌 Note Reminder')

                    ->body(strip_tags($note->description))
                    ->success()
                    ->sendToDatabase($user);

                $note->forceFill([
                        'reminder_sent_at' => $now
                    ])->save();      
                    
                    
                \Log::info("✅ Notification sent for Note ID {$note->id} to user {$user->id}");
            } else {
                \Log::warning("⚠️ No user found for Note ID {$note->id}");
            }
        }
    }
}
