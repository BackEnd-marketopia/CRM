<?php

namespace App\Filament\Widgets;

use App\Models\Task;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class TaskOverview extends BaseWidget
{
    protected function getCards(): array
    {
        if(auth()->user()->isAdmin()) {
        return [
            Card::make('Total Tasks', Task::count()),
            Card::make('Completed Tasks', Task::where('is_completed', 1)->count()),
            Card::make('Incomplete Tasks', Task::where('is_completed', '!=', 1)->count()),
        ];
        }elseif(!auth()->user()->isAdmin()) {
            $employeeId = auth()->id();
            return [
                Card::make('Total Tasks', Task::where('user_id', $employeeId)->count()),
                Card::make('Completed Tasks', Task::where('user_id', $employeeId)->where('is_completed', 1)->count()),
                Card::make('Incomplete Tasks', Task::where('user_id', $employeeId)->where('is_completed', '!=', 1)->count()),
            ];
        }
    }
}
