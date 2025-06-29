<?php

namespace App\Livewire;

use Livewire\Component;
use Filament\Notifications\Notification;

class NotificationsPopup extends Component
{
    public function mount()
    {
        $user = auth()->user();

          if (! $user) {
            return;
        }


        foreach ($user->unreadNotifications as $notification) {
            $title = $notification->data['title'] ?? '📌 Notification';
            $body = $notification->data['body'] ?? 'You have a new notification';

            Notification::make()
            ->title($title)
            ->body($body)
            ->success()
            ->send();

            $notification->markAsRead(); 
        }
    }

    public function render()
    {
        return view('livewire.notifications-popup');
    }
}
