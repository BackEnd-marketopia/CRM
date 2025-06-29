<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CustomerOverview;
use App\Filament\Widgets\NoteChart;
use App\Filament\Widgets\NoteOverview;
use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Widgets\TaskOverview;
use App\Filament\Widgets\TaskChart;
use App\Http\Livewire\NotificationsPopup;

class Dashboard extends BaseDashboard
{
    protected function getHeaderWidgets(): array
    {
        
        return [
            // CustomerOverview::class,
            TaskOverview::class,
            NoteOverview::class,
            TaskChart::class,
            NoteChart::class,
            NotificationsPopup::class,
        ];
    }
}
