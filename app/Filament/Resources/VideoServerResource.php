<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VideoServerResource\Pages;
use App\Filament\Resources\VideoServerResource\RelationManagers;
use App\Models\VideoServer;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VideoServerResource extends Resource
{
    protected static ?string $model = VideoServer::class;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('server_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('embed_url')
                    ->label('Embed URL')
                    ->required()
                    ->rows(3),
                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->required(),
                Forms\Components\Select::make('episode_id')
                    ->relationship('episode', 'title')
                    ->searchable()
                    ->preload()
                    ->placeholder('Cari & pilih episode')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('server_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('episode.title')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('duplicate')
                    ->label('Copy')
                    ->icon('heroicon-o-duplicate')
                    ->color('success')
                    ->action(function (VideoServer $record) {
                        $newServer = $record->replicate();
                        $newServer->server_name = $record->server_name . ' (Copy)';
                        $newServer->save();
                        
                        return redirect(static::getUrl('edit', ['record' => $newServer->id]));
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Duplikasi Video Server')
                    ->modalSubheading('Server akan dicopy dengan nama "(Copy)" di belakang')
                    ->modalButton('Ya, Copy Server'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListVideoServers::route('/'),
            'create' => Pages\CreateVideoServer::route('/create'),
            'edit' => Pages\EditVideoServer::route('/{record}/edit'),
        ];
    }    
}
