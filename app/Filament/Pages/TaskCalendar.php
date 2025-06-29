<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class TaskCalendar extends Page
{
    protected ?string $heading = 'Calendar';
    protected static ?string $navigationLabel = 'Calendar';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.task-calendar';
}
