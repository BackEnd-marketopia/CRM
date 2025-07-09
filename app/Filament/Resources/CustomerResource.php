<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers;
use App\Filament\Resources\QuoteResource\Pages\CreateQuote;
use App\Models\Customer;
use App\Models\CustomField;
use App\Models\PipelineStage;
use App\Models\Role;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists\Components\Actions\Action;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Filament\Tables\Actions\BulkAction;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Employee Information')
                    ->schema([
                        Forms\Components\Select::make('employee_id')
                            ->options(User::where('role_id', Role::where('name', 'Employee')->first()->id)->pluck('name', 'id'))
                    ])
                    ->hidden(!auth()->user()->isAdmin()),
                Forms\Components\Section::make('Customer Details')
                    ->schema([
                        Forms\Components\TextInput::make('first_name')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('last_name')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone_number')
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->columns(),
                Forms\Components\Section::make('Lead Details')
                    ->schema([
                        Forms\Components\Select::make('lead_source_id')
                            ->relationship('leadSource', 'name'),
                        Forms\Components\Select::make('tags')
                            ->relationship('tags', 'name')
                            ->multiple(),
                        Forms\Components\Select::make('pipeline_stage_id')
                            ->relationship('pipelineStage', 'name', function ($query) {
                                $query->orderBy('position', 'asc');
                            })
                            ->default(PipelineStage::where('is_default', true)->first()?->id)
                    ])
                    ->columns(3),
                Forms\Components\Section::make('Rejection Details')
                    ->schema([
                        Forms\Components\Select::make('rejection_status')
                            ->options([
                                'the price' => 'The Price',
                                'Contract with another company' => 'Contract with another company',
                                'trust' => 'Trust',
                                'Unqualified Lead' => 'Unqualified Lead',
                                'Other' => 'Other',
                            ])
                            ->nullable()
                            ->label('Rejection Status'),
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Rejection Reason')
                            ->maxLength(65535)
                            ->hidden(fn($get) => $get('rejection_status') !== 'Other'),
                    ])
                    ->columns(),
                Forms\Components\Section::make('Documents')
                    ->visibleOn('edit')
                    ->schema([
                        Forms\Components\Repeater::make('documents')
                            ->relationship('documents')
                            ->hiddenLabel()
                            ->reorderable(false)
                            ->addActionLabel('Add Document')
                            ->schema([
                                Forms\Components\FileUpload::make('file_path')
                                    ->required(),
                                Forms\Components\Textarea::make('comments'),
                            ])
                            ->columns()
                    ]),
                Forms\Components\Section::make('Additional fields')
                    ->schema([
                        Forms\Components\Repeater::make('fields')
                            ->hiddenLabel()
                            ->relationship('customFields')
                            ->schema([
                                Forms\Components\Select::make('custom_field_id')
                                    ->label('Field Type')
                                    ->options(CustomField::pluck('name', 'id')->toArray())
                                    ->disableOptionWhen(function ($value, $state, Get $get) {
                                        return collect($get('../*.custom_field_id'))
                                            ->reject(fn($id) => $id === $state)
                                            ->filter()
                                            ->contains($value);
                                    })
                                    ->required()
                                    ->searchable()
                                    ->live(),
                                Forms\Components\TextInput::make('value')
                                    ->required()
                            ])
                            ->addActionLabel('Add another Field')
                            ->columns(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                return $query->with('tags');
            })
            ->columns([
                Tables\Columns\TextColumn::make('id'),
                 Tables\Columns\TextColumn::make('first_name')
                    ->label('Customer Name')
                    ->formatStateUsing(function ($record) {
                        $tagsList = view('customer.tagsList', ['tags' => $record->tags])->render();

                        return $record->first_name . ' ' . $record->last_name . ' ' . $tagsList;
                    })
                    ->html()    
                    ->searchable(['first_name', 'last_name']),
                Tables\Columns\TextColumn::make('employee.name')
                    ->label('Sales')
                    ->hidden(!auth()->user()->isAdmin())
                    ->searchable(),
                // Tables\Columns\TextColumn::make('email')
                //     ->searchable(),
                Tables\Columns\TextColumn::make('phone_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('rejection_status')
                    ->label('Rejection Status')
                    ->getStateUsing(fn($record) => $record->rejection_status ?? null)
                    ->formatStateUsing(function ($state) {
                        return $state ? ucfirst($state) : '-';
                    })
                    ->toggleable(),
                    Tables\Columns\TextColumn::make('tasks')
                        ->label('Last Task Assigned')
                        ->formatStateUsing(function ($record) {
                            $lastTask = $record->tasks()->latest('created_at')->first();
                            return $lastTask ? \Illuminate\Support\Str::limit(strip_tags($lastTask->description), 40) : '-';
                        })
                        ->html()
                        ->toggleable(),
                      Tables\Columns\TextColumn::make('notes')
                        ->label('Last Note Added')
                        ->formatStateUsing(function ($record) {
                            $lastNote = $record->notes()->latest('created_at')->first();
                            return $lastNote ? \Illuminate\Support\Str::limit(strip_tags($lastNote->description), 40) : '-';
                        })
                        ->html()
                        ->toggleable(),
                Tables\Columns\TextColumn::make('leadSource.name'),
                Tables\Columns\TextColumn::make('pipelineStage.name')
                ->label('Status'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->hidden(fn($record) => $record->trashed()),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                    Tables\Actions\Action::make('Change Status')
                        ->hidden(fn($record) => $record->trashed())
                        ->icon('heroicon-m-pencil-square')
                        ->form([
                            Forms\Components\Select::make('pipeline_stage_id')
                                ->label('Status')
                                ->options(PipelineStage::pluck('name', 'id')->toArray())
                                ->default(function (Customer $record) {
                                    $currentPosition = $record->pipelineStage->position;
                                    return PipelineStage::where('position', '>', $currentPosition)->first()?->id;
                                }),
                            Forms\Components\Textarea::make('notes')
                                ->label('Notes')
                        ])
                        ->action(function (Customer $customer, array $data): void {
                            $customer->pipeline_stage_id = $data['pipeline_stage_id'];
                            $customer->save();

                            $customer->pipelineStageLogs()->create([
                                'pipeline_stage_id' => $data['pipeline_stage_id'],
                                'notes' => $data['notes'],
                                'user_id' => auth()->id()
                            ]);

                            Notification::make()
                                ->title('Customer Pipeline Updated')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\Action::make('Add Task')
                        ->icon('heroicon-s-clipboard-document')
                        ->form([
                            Forms\Components\RichEditor::make('description')
                                ->required(),
                            Forms\Components\Select::make('user_id')
                                ->preload()
                                ->searchable()
                                ->relationship('employee', 'name'),
                            Forms\Components\DatePicker::make('due_date')
                                ->native(false),

                        ])
                        ->action(function (Customer $customer, array $data) {
                            $customer->tasks()->create($data);

                            Notification::make()
                                ->title('Task created successfully')
                                ->success()
                                ->send();
                        })
                        ->visible(fn () => auth()->user()?->isAdmin()),
                        Tables\Actions\Action::make('Change Rejection Status')
                            ->icon('heroicon-o-x-circle')
                            ->hidden(fn($record) => $record->trashed())
                            ->form([
                                Forms\Components\Select::make('rejection_status')
                                    ->label('Rejection Status')
                                    ->options([
                                        'the price' => 'The Price',
                                        'Contract with another company' => 'Contract with another company',
                                        'trust' => 'Trust',
                                        'Unqualified Lead' => 'Unqualified Lead',
                                        'Other' => 'Other',
                                    ])
                                    ->required(),
                            ])
                            ->action(function (Customer $customer, array $data) {
                                $customer->rejection_status = $data['rejection_status'];
                                $customer->save();

                                Notification::make()
                                    ->title('Rejection status updated')
                                    ->success()
                                    ->send();
                            }),
                    // Tables\Actions\Action::make('Create Quote')
                    //     ->icon('heroicon-m-book-open')
                    //     ->url(function ($record) {
                    //         return CreateQuote::getUrl(['customer_id' => $record->id]);
                    //     })
                ])
            ])
            ->recordUrl(function ($record) {
                if ($record->trashed()) {
                    return null;
                }

                return Pages\ViewCustomer::getUrl([$record->id]);
            })
            ->bulkActions([
                BulkAction::make('changeEmployee')
                    ->label('Change Sales')
                    ->icon('heroicon-o-user')
                    ->form([
                        Forms\Components\Select::make('employee_id')
                            ->label('Select Employee')
                            ->options(function () {
                                $roleId = Role::where('name', 'Employee')->first()?->id;
                                // تأكد أن $roleId ليس null قبل الاستعلام
                                if (!$roleId) {
                                    return [];
                                }
                                return User::where('role_id', $roleId)->pluck('name', 'id');
                            })
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (array $data, $records) {
                        foreach ($records as $record) {
                            $record->update([
                                'employee_id' => $data['employee_id'],
                            ]);
                        }

                        Notification::make()
                            ->title('Sales assigned to selected customers')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->visible(fn () => auth()->user()?->isAdmin()), 
                Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn () => auth()->user()?->isAdmin()), 
            ]);
    }

    public static function infoList(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Personal Information')
                    ->schema([
                        TextEntry::make('first_name'),
                        TextEntry::make('last_name'),
                    ])
                    ->columns(),
                Section::make('Contact Information')
                    ->schema([
                        TextEntry::make('email'),
                        TextEntry::make('phone_number'),
                    ])
                    ->columns(),
                Section::make('Additional Details')
                    ->schema([
                        TextEntry::make('description'),
                    ]),
                Section::make('Lead, Stage Information and Rejection Status')
                    ->schema([
                        TextEntry::make('leadSource.name'),
                        TextEntry::make('pipelineStage.name'),
                        TextEntry::make('rejection_status')
                            ->label('Rejection Status')
                            ->formatStateUsing(fn($state) => $state ? ucfirst($state) : '-'),
                    ])
                    ->columns(),
                Section::make('Additional fields')
                    ->hidden(fn($record) => $record->customFields->isEmpty())
                    ->schema(
                        fn($record) => $record->customFields->map(function ($customField) {
                            return TextEntry::make($customField->customField->name)
                                ->label($customField->customField->name)
                                ->default($customField->value);
                        })->toArray()
                    )
                    ->columns(),
                Section::make('Documents')
                    ->hidden(fn($record) => $record->documents->isEmpty())
                    ->schema([
                        RepeatableEntry::make('documents')
                            ->hiddenLabel()
                            ->schema([
                                TextEntry::make('file_path')
                                    ->label('Document')
                                    ->formatStateUsing(fn() => "Download Document")
                                    ->url(fn($record) => Storage::url($record->file_path), true)
                                    ->badge()
                                    ->color(Color::Blue),
                                TextEntry::make('comments'),
                            ])
                            ->columns()
                    ]),
                Section::make('Pipeline Stage History and Notes')
                    ->schema([
                        ViewEntry::make('pipelineStageLogs')
                            ->label('')
                            ->view('infolists.components.pipeline-stage-history-list')
                    ])
                    ->collapsible(),
                Tabs::make('Tasks')
                    ->tabs([
                        Tabs\Tab::make('Completed Tasks')
                            ->badge(fn($record) => $record->completedTasks->count())
                            ->schema([
                                RepeatableEntry::make('completedTasks')
                                    ->hiddenLabel()
                                    ->schema([
                                        TextEntry::make('description')
                                            ->html()
                                            ->columnSpanFull(),
                                        TextEntry::make('employee.name')
                                            ->hidden(fn($state) => is_null($state)),
                                        TextEntry::make('due_date')
                                            ->hidden(fn($state) => is_null($state))
                                            ->date(),
                                    ])
                                    ->columns()
                            ]),
                        Tabs\Tab::make('Incomplete Tasks')
                            ->badge(fn($record) => $record->incompleteTasks->count())
                            ->schema([
                                RepeatableEntry::make('incompleteTasks')
                                    ->hiddenLabel()
                                    ->schema([
                                        TextEntry::make('description')
                                            ->html()
                                            ->columnSpanFull(),
                                        TextEntry::make('employee.name')
                                            ->hidden(fn($state) => is_null($state)),
                                        TextEntry::make('due_date')
                                            ->hidden(fn($state) => is_null($state))
                                            ->date(),
                                        TextEntry::make('is_completed')
                                            ->formatStateUsing(function ($state) {
                                                return $state ? 'Yes' : 'No';
                                            })
                                            ->suffixAction(
                                                Action::make('complete')
                                                    ->button()
                                                    ->requiresConfirmation()
                                                    ->modalHeading('Mark task as completed?')
                                                    ->modalDescription('Are you sure you want to mark this task as completed?')
                                                    ->action(function (Task $record) {
                                                        $record->is_completed = true;
                                                        $record->save();

                                                        Notification::make()
                                                            ->title('Task marked as completed')
                                                            ->success()
                                                            ->send();
                                                    })
                                            ),
                                    ])
                                    ->columns(3)
                                ])
                            ])
                            ->columnSpanFull(),
                        Tabs::make('Notes')
                            ->tabs([
                                Tabs\Tab::make('Completed Notes')
                                    ->badge(fn($record) => $record->completedNotes ? $record->completedNotes->count() : 0)
                                    ->schema([
                                        RepeatableEntry::make('completedNotes')
                                            ->hiddenLabel()
                                            ->schema([
                                                TextEntry::make('notes')
                                                    ->label('Note')
                                                    ->html()
                                                    ->columnSpanFull(),
                                                TextEntry::make('description')
                                                    ->label('Description')
                                                    ->html()
                                                    ->columnSpanFull(),
                                                TextEntry::make('user.name')
                                                    ->label('Added By')
                                                    ->hidden(fn($state) => is_null($state)),
                                                TextEntry::make('created_at')
                                                    ->label('Date')
                                                    ->dateTime()
                                                    ->hidden(fn($state) => is_null($state)),
                                            ])
                                            ->columns()
                                    ]),
                                Tabs\Tab::make('Incomplete Notes')
                                    ->badge(fn($record) => $record->incompleteNotes ? $record->incompleteNotes->count() : 0)
                                    ->schema([
                                        RepeatableEntry::make('incompleteNotes')
                                            ->hiddenLabel()
                                            ->schema([
                                                TextEntry::make('notes')
                                                    ->label('Note')
                                                    ->html()
                                                    ->columnSpanFull(),
                                                TextEntry::make('description')
                                                    ->label('Description')
                                                    ->html()
                                                    ->columnSpanFull(),
                                                TextEntry::make('user.name')
                                                    ->label('Added By')
                                                    ->hidden(fn($state) => is_null($state)),
                                                TextEntry::make('created_at')
                                                    ->label('Date')
                                                    ->dateTime()
                                                    ->hidden(fn($state) => is_null($state)),
                                                TextEntry::make('is_completed')
                                                    ->label('Completed')
                                                    ->formatStateUsing(function ($state) {
                                                        return $state ? 'Yes' : 'No';
                                                    }),
                                            ])
                                            ->columns()
                                    ]),
                            ])
                            ->columnSpanFull(),
                        ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
            'view' => Pages\ViewCustomer::route('/{record}'),
        ];
    }

    public static function canEdit(Model $record): bool
    {
        if (!auth()->user()->isAdmin()) {
            return false;
        }
        return true;
    }

    public static function canDelete(Model $record): bool
    {
        if (!auth()->user()->isAdmin()) {
            return false;
        }
        return true;
    }
}
