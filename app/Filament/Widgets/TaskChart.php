<?php

namespace App\Filament\Widgets;

use App\Models\Task;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class TaskChart extends ChartWidget
{
    protected static ?string $heading = 'Number of tasks by month';
    protected static ?int $sort = 2; 

    protected function getData(): array 
    {
        // Get task counts by month
        $data = Task::selectRaw('MONTH(due_date_time) as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month');

        // Prepare all months
        $months = collect(range(1, 12));
        $labels = $months->map(fn($month) => now()->startOfYear()->addMonths($month - 1)->format('F'));
        $counts = $months->map(fn($month) => $data->get($month, 0));

        return [
            'datasets' => [
                [
                    'label' => 'Number of tasks',
                    'data' => $counts,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'pie'; // bar أو 'line', 'pie'
    }
}
