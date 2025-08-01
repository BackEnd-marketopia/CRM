<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PipelineStageResource\Pages;
use App\Filament\Resources\PipelineStageResource\RelationManagers;
use App\Models\PipelineStage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PipelineStageResource extends Resource
{
    protected static ?string $model = PipelineStage::class;
    protected static ?string $title = "Status";
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Status';
    protected static ?string $modelLabel = 'Statu';



    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_default')
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
            ->defaultSort('position')
            ->reorderable('position')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('Set Default')
                    ->icon('heroicon-o-star')
                    ->hidden(fn($record) => $record->is_default)
                    ->requiresConfirmation(function (Tables\Actions\Action $action, $record) {
                        $action->modalDescription('Are you sure you want to set this as the default Status?');
                        $action->modalHeading('Set "' . $record->name . '" as Default');

                        return $action;
                    })
                    ->action(function (PipelineStage $record) {
                        PipelineStage::where('is_default', true)->update(['is_default' => false]);

                        $record->is_default = true;
                        $record->save();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()// [tl! add:start]
                ->action(function ($data, $record) {
                    if ($record->customers()->count() > 0) {
                        Notification::make()
                            ->danger()
                            ->title('Status is in use')
                            ->body('Status is in use by customers.')
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->success()
                        ->title('Status deleted')
                        ->body('Status has been deleted.')
                        ->send();

                    $record->delete();
                })
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
            'index' => Pages\ListPipelineStages::route('/'),
            'create' => Pages\CreatePipelineStage::route('/create'),
            'edit' => Pages\EditPipelineStage::route('/{record}/edit'),
        ];
    }


     public static function canAccess(): bool
    {
        return auth()->user()->isAdmin() || auth()->user()->isDataEntryManager() || auth()->user()->isDataEntry();
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
