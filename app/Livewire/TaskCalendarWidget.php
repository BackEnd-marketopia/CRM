<?php

namespace App\Livewire;

use App\Filament\Resources\TaskResource;
use App\Models\Task;
use App\Models\Note;

use Saade\FilamentFullCalendar\Data\EventData;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class TaskCalendarWidget extends FullCalendarWidget
{
    public function fetchEvents(array $fetchInfo): array
    {
        $tasks = Task::query()
            ->where('due_date', '>=', $fetchInfo['start'])
            ->where('due_date', '<=', $fetchInfo['end'])
            ->when(!auth()->user()->isAdmin(), function ($query) {
                return $query->where('user_id', auth()->id());
            })
            ->get()
            ->map(
                fn(Task $task) => array_merge(
                    tap(
                        EventData::make()
                            ->id('task-' . $task->getKey())
                            ->title(strip_tags($task->description))
                            ->start($task->due_date)
                            ->end($task->due_date),
                        function ($event) use ($task) {
                            if (auth()->user()->isAdmin()) {
                                $event->url(TaskResource::getUrl('edit', [$task->getKey()]));
                            }
                        }
                    )->toArray(),
                    ['color' => 'red']
                )
            );

        $notes = Note::query()
            ->where('due_date_time', '>=', $fetchInfo['start'])
            ->where('due_date_time', '<=', $fetchInfo['end'])
            ->when(!auth()->user()->isAdmin(), function ($query) {
                return $query->where('created_by', auth()->id());
            })
            ->get()
            ->map(
                fn(Note $note) => array_merge(
                    EventData::make()
                        ->id('note-' . $note->getKey())
                        ->title(strip_tags($note->description))
                        ->start($note->due_date_time)
                        ->end($note->due_date_time)
                        ->url(TaskResource::getUrl('edit', [$note->getKey()]))
                        ->toArray(),
                        ['color' => 'blue']
                )
            );

        return collect($tasks)->concat($notes)->values()->toArray();
    }
}
