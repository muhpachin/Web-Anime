<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AnimeResource\Pages;
use App\Filament\Resources\AnimeResource\RelationManagers;
use App\Models\Anime;
use App\Models\Genre;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class AnimeResource extends Resource
{
    protected static ?string $model = Anime::class;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set, $state) => $set('slug', Str::slug($state))),
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Auto-generated dari title'),
                        Forms\Components\Textarea::make('synopsis')
                            ->required()
                            ->rows(5),
                        Forms\Components\FileUpload::make('poster_image')
                            ->image()
                            ->directory('posters')
                            ->visibility('public')
                            ->getUploadedFileNameForStorageUsing(function (UploadedFile $file, callable $get): string {
                                $slug = $get('slug') ?: Str::slug($get('title'));
                                return $slug . '-' . time() . '.' . $file->getClientOriginalExtension();
                            })
                            ->helperText('Upload poster anime (JPG/PNG)'),
                    ])
                    ->columnSpan(2),
                
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->options(['TV' => 'TV', 'Movie' => 'Movie', 'ONA' => 'ONA'])
                            ->default('TV')
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->options(['Ongoing' => 'Ongoing', 'Completed' => 'Completed'])
                            ->default('Ongoing')
                            ->required(),
                        Forms\Components\TextInput::make('release_year')
                            ->numeric()
                            ->minValue(1900)
                            ->maxValue(date('Y') + 1)
                            ->helperText('Tahun rilis anime'),
                        Forms\Components\TextInput::make('rating')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(10)
                            ->step(0.01)
                            ->helperText('Rating 0-10 (gunakan titik untuk desimal, misal: 7.63)')
                            ->placeholder('7.6'),
                        Forms\Components\Select::make('genres')
                            ->multiple()
                            ->relationship('genres', 'name')
                            ->preload()
                            ->required()
                            ->helperText('Pilih satu atau lebih genre'),
                        Forms\Components\Toggle::make('featured')
                            ->label('Tampilkan di Featured')
                            ->helperText('Tampilkan anime ini di spotlight homepage'),
                    ])
                    ->columnSpan(1),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('poster_image')
                    ->label('Poster')
                    ->width(50)
                    ->height(75)
                    ->getStateUsing(fn ($record) => $record->poster_image ? 'storage/' . $record->poster_image : null),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('genres_list')
                    ->label('Genres')
                    ->getStateUsing(fn ($record) => $record->genres->pluck('name')->take(3)->join(', '))
                    ->wrap(),
                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'success' => 'TV',
                        'warning' => 'Movie',
                        'info' => 'ONA',
                    ]),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'Completed',
                        'warning' => 'Ongoing',
                    ]),
                Tables\Columns\TextColumn::make('release_year')
                    ->label('Tahun')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ?? '-'),
                Tables\Columns\TextColumn::make('rating')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1) . '/10' : '-'),
                Tables\Columns\IconColumn::make('featured')
                    ->boolean()
                    ->label('Featured'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'TV' => 'TV',
                        'Movie' => 'Movie',
                        'ONA' => 'ONA',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'Ongoing' => 'Ongoing',
                        'Completed' => 'Completed',
                    ]),
                Tables\Filters\SelectFilter::make('release_year')
                    ->label('Tahun Rilis')
                    ->options(function () {
                        return \App\Models\Anime::whereNotNull('release_year')
                            ->distinct()
                            ->orderBy('release_year', 'desc')
                            ->pluck('release_year', 'release_year')
                            ->toArray();
                    })
                    ->placeholder('Semua Tahun'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('sync_videos')
                    ->label('Sync Videos')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->form([
                        Forms\Components\TextInput::make('source_url')
                            ->label('Source URL (optional)')
                            ->placeholder('https://animesail.in/...')
                            ->helperText('Kosongkan untuk auto-search berdasarkan judul'),
                    ])
                    ->action(function (Anime $record, array $data) {
                        $service = app(\App\Services\AnimeSailService::class);

                        $url = $data['source_url'] ?? null;
                        if (empty($url)) {
                            $results = $service->searchAnime($record->title);
                            $url = $results[0]['url'] ?? null;
                        }

                        if (!$url) {
                            \Filament\Notifications\Notification::make()
                                ->title('Source not found')
                                ->danger()
                                ->body('Tidak ditemukan sumber video untuk judul ini.')
                                ->send();
                            return;
                        }

                        $result = $service->syncEpisodesForAnime($record, $url);

                        \Filament\Notifications\Notification::make()
                            ->title('Sync videos completed')
                            ->success()
                            ->body("Created: {$result['created']} | Updated: {$result['updated']} | Errors: " . count($result['errors']))
                            ->send();
                    })
                        ->requiresConfirmation(),
                    ])
                    ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->label('Hapus Terpilih'),
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
            'index' => Pages\ListAnimes::route('/'),
            'create' => Pages\CreateAnime::route('/create'),
            'edit' => Pages\EditAnime::route('/{record}/edit'),
        ];
    }    
}
