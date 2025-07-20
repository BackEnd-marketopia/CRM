<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NoteResource\Pages;
use App\Filament\Resources\NoteResource\RelationManagers;
use App\Models\Note;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use App\Models\Customer;


class NoteResource extends Resource
{
    protected static ?string $model = Note::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                 auth()->user()->isAdmin()
                    ? Forms\Components\Select::make('created_by')
                        ->label('Sales')
                        ->relationship('createdBy', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                    : Forms\Components\Select::make('created_by')
                        ->label('Sales')
                        ->relationship('createdBy', 'name')
                        ->default(auth()->id())
                        ->disabled()
                        ->dehydrated(true) // ← ✅ دي اللي بتخلي القيمة تتبعت حتى وهي disabled
                        ->required(),
                auth()->user()->isAdmin()
                    ? Forms\Components\Select::make('customer_id')
                        ->searchable()
                        ->relationship('customer')
                        ->getOptionLabelFromRecordUsing(fn(Customer $record) => $record->first_name . ' ' . $record->last_name)
                        ->searchable(['first_name', 'last_name'])
                        ->required()
                    : Forms\Components\Select::make('customer_id')
                        ->searchable()
                        ->options(function () {
                            $employeeId = auth()->id();
                            if (!$employeeId) {
                                return [];
                            }
                            $customerIds = \App\Models\Customer::where('employee_id', $employeeId)
                                ->pluck('id')
                                ->toArray();
                            return Customer::whereIn('id', $customerIds)
                                ->get()
                                ->mapWithKeys(function ($customer) {
                                    return [
                                        $customer->id => $customer->first_name . ' ' . $customer->last_name
                                    ];
                                })
                                ->toArray();
                        })
                        ->disabled(fn($context) => $context === 'edit')
                        ->required(),
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
                Tables\Columns\TextColumn::make('createdBy.name')
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
                    ->hidden(fn(Note $record) => $record->is_completed)
                    ->icon('heroicon-m-check-badge')
                    ->modalHeading('Mark note as completed?')
                    ->modalDescription('Are you sure you want to mark this note as completed?')
                    ->action(function (Note $record) {
                        $record->is_completed = true;
                        $record->save();

                        Notification::make()
                            ->title('Note marked as completed')
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
            'index' => Pages\ListNotes::route('/'),
            'create' => Pages\CreateNote::route('/create'),
            'edit' => Pages\EditNote::route('/{record}/edit'),
        ];
    }  
    
    
       public static function canAccess(): bool
    {
        return auth()->user()->isAdmin() || auth()->user()->isDataEntryManager() || auth()->user()->isDataEntry() || auth()->user()->isSales();
    }

      public static function canView(Model $record): bool
    {
        return auth()->user()->isAdmin() || auth()->user()->isDataEntryManager() || auth()->user()->isDataEntry() || auth()->user()->isSales();
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()->isAdmin() || auth()->user()->isDataEntryManager() || auth()->user()->isDataEntry() || auth()->user()->isSales();
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()->isAdmin() || auth()->user()->isDataEntryManager() || auth()->user()->isDataEntry() || auth()->user()->isSales();
    }
}
