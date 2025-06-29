<?php

namespace App\Filament\Widgets;

use App\Models\Note;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class NoteOverview extends BaseWidget
{
    protected function getCards(): array
    {
        if(auth()->user()->isAdmin()) {
        return [
            Card::make('Total Notes', Note::count()),
            Card::make('Completed Notes', Note::where('is_completed', 1)->count()),
            Card::make('Incomplete Notes', Note::where('is_completed', '!=', 1)->count()),
        ];
        }elseif(!auth()->user()->isAdmin()) {
            $employeeId = auth()->id();
            return [
                Card::make('Total Notes', Note::where('created_by', $employeeId)->count()),
                Card::make('Completed Notes', Note::where('created_by', $employeeId)->where('is_completed', 1)->count()),
                Card::make('Incomplete Notes', Note::where('created_by', $employeeId)->where('is_completed', '!=', 1)->count()),
            ];
        }
        // Default return if no condition matches
        return [];
    }
}
