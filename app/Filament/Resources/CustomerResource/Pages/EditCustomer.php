<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        if (auth()->user()->isAdmin() || auth()->user()->isDataEntryManager()) {
            return [
                Actions\DeleteAction::make(),
            ];
        }
        return [];
    }
}
