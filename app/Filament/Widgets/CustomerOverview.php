<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class CustomerOverview extends BaseWidget
{
    protected function getCards(): array
    {
        // if(auth()->user()->isAdmin()) {
        // return [
        //     Card::make('Total Customers', Customer::count()),
        // ];
        // }elseif(!auth()->user()->isAdmin()) {
        //     $employeeId = auth()->id();
        //     return [
        //         Card::make('Total Customers', Customer::where('employee_id', $employeeId)->count()),
        //     ];
        // }

        // Default return if no condition matches
        return [];
    }
}
