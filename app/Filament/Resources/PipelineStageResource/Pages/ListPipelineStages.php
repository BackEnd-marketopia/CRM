<?php

namespace App\Filament\Resources\PipelineStageResource\Pages;

use App\Filament\Resources\PipelineStageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPipelineStages extends ListRecords
{
    protected static string $resource = PipelineStageResource::class;
    protected static ?string $title = 'Status';
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Create Status'),
        ];
    }
}
