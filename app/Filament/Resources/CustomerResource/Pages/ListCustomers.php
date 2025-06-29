<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Models\Customer;
use App\Models\PipelineStage;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;


class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        if (auth()->user()->isAdmin()) {
            return [
                Actions\CreateAction::make(),
            ];
        }

        return [];
    }

    public function getTabs(): array
    {
        $tabs = [];

        if (auth()->user()->isAdmin()) {
        $tabs['all'] = Tab::make('All Customers')
            ->badge(Customer::count());
        }

        if (!auth()->user()->isAdmin()) {
            $tabs['my'] = Tab::make('My Customers')
            ->badge(Customer::where('employee_id', auth()->id())->count())
            ->modifyQueryUsing(function ($query) {
                return $query->where('employee_id', auth()->id());
            });
        }


        $pipelineStages = PipelineStage::orderBy('position')->withCount([
            'customers' => function ($query) {
            if (!auth()->user()->isAdmin()) {
                $query->where('employee_id', auth()->id());
            }
            }
        ])->get();

        foreach ($pipelineStages as $pipelineStage) {
            $tabs[str($pipelineStage->name)->slug()->toString()] = Tab::make($pipelineStage->name)
            ->badge($pipelineStage->customers_count)
            ->modifyQueryUsing(function ($query) use ($pipelineStage) {
                if (!auth()->user()->isAdmin()) {
                return $query->where('pipeline_stage_id', $pipelineStage->id)
                         ->where('employee_id', auth()->id());
                }
                return $query->where('pipeline_stage_id', $pipelineStage->id);
            });
        }

        $tabs['archived'] = Tab::make('Archived')
            ->badge(Customer::onlyTrashed()->count())
            ->modifyQueryUsing(function ($query) {
                return $query->onlyTrashed();
            });

        return $tabs;
    }
}
