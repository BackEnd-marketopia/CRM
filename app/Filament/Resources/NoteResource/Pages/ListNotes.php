<?php

namespace App\Filament\Resources\NoteResource\Pages;

use App\Filament\Resources\NoteResource;
use App\Models\Note;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListNotes extends ListRecords
{
    protected static string $resource = NoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

       public function getTabs(): array
    {
        $tabs = [];

        if (!auth()->user()->isAdmin()) {
            $tabs[] = Tab::make('My Notes')
                ->badge(Note::where('created_by', auth()->id())->count())
                ->modifyQueryUsing(function ($query) {
                    return $query->where('created_by', auth()->id());
                });
        }

        if (auth()->user()->isAdmin()) {
        $tabs[] = Tab::make('All Notes')
            ->badge(Note::count());
        }

        if (auth()->user()->isAdmin()) {
        $tabs[] = Tab::make('Completed Notes')
            ->badge(Note::where('is_completed', true)->count())
            ->modifyQueryUsing(function ($query) {
                return $query->where('is_completed', true);
            });
        }elseif (!auth()->user()->isAdmin()) {
            $tabs[] = Tab::make('Completed Notes')
                ->badge(Note::where('is_completed', true)->where('created_by', auth()->id())->count())
                ->modifyQueryUsing(function ($query) {
                    return $query->where('is_completed', true)->where('created_by', auth()->id());
                });
        }


        if(auth()->user()->isAdmin()){
        $tabs[] = Tab::make('Incomplete Notes')
            ->badge(Note::where('is_completed', false)->count())
            ->modifyQueryUsing(function ($query) {
                return $query->where('is_completed', false);
            });
        }elseif (!auth()->user()->isAdmin()){
            $tabs[] = Tab::make('Incomplete Notes')
            ->badge(Note::where('is_completed', false)->where('created_by', auth()->id())->count())
            ->modifyQueryUsing(function ($query) {
                    return $query->where('is_completed', true)->where('created_by', auth()->id());
            });
         }
         
        return $tabs;
    }
}
