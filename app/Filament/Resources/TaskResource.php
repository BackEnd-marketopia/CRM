<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaskResource\Pages;
use App\Filament\Resources\TaskResource\RelationManagers;
use App\Models\Customer;
use App\Models\Task;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('customer_id')
                    ->searchable()
                    ->relationship('customer')
                    ->getOptionLabelFromRecordUsing(fn(Customer $record) => $record->first_name . ' ' . $record->last_name)
                    ->searchable(['first_name', 'last_name'])
                    ->required(),
                Forms\Components\Select::make('user_id')
                    ->preload()
                    ->searchable()
                    ->relationship('employee', 'name'),
                Forms\Components\RichEditor::make('description')
                    ->required()
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('due_date_time')
                    ->required()
                    ->displayFormat('Y-m-d H:i:s')
                    ->default(now()),
                Forms\Components\Toggle::make('is_completed')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer.first_name')
                    ->formatStateUsing(function ($record) {
                        return $record->customer->first_name . ' ' . $record->customer->last_name;
                    })
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('employee.name')
                    ->label('Sales')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->html(),
               Tables\Columns\TextColumn::make('due_date_time')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_completed')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('Complete')
                    ->hidden(fn(Task $record) => $record->is_completed)
                    ->icon('heroicon-m-check-badge')
                    ->modalHeading('Mark task as completed?')
                    ->modalDescription('Are you sure you want to mark this task as completed?')
                    ->action(function (Task $record) {
                        $record->is_completed = true;
                        $record->save();

                        Notification::make()
                            ->title('Task marked as completed')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort(function ($query) {
                return $query->orderBy('due_date_time', 'asc')
                    ->orderBy('id', 'desc');
            });
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
            'index' => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'edit' => Pages\EditTask::route('/{record}/edit'),
        ];
    }

      public static function canAccess(): bool
    {
        return auth()->user()->isAdmin() || auth()->user()->isDataEntryManager() || auth()->user()->isDataEntry() || auth()->user()->isSales();
    }

      public static function canView(Model $record): bool
    {
        return auth()->user()->isAdmin() || auth()->user()->isDataEntryManager() || auth()->user()->isDataEntry();
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()->isAdmin() || auth()->user()->isDataEntryManager() || auth()->user()->isDataEntry();
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()->isAdmin() || auth()->user()->isDataEntryManager() || auth()->user()->isDataEntry();
    }
}
