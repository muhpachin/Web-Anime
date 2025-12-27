<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EpisodeResource\Pages;
use App\Filament\Resources\EpisodeResource\RelationManagers;
use App\Models\Episode;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Support\Str;

class EpisodeResource extends Resource
{
    protected static ?string $model = Episode::class;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('episode_number')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->reactive()
                    ->afterStateUpdated(fn (callable $set, $state) => $set('slug', Str::slug($state))),
                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->helperText('Auto-generated dari title'),
                Forms\Components\Textarea::make('description'),
                Forms\Components\Select::make('anime_id')
                    ->relationship('anime', 'title')
                    ->searchable()
                    ->preload()
                    ->placeholder('Cari & pilih anime')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('episode_number')
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('anime.title')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->defaultSort('episode_number', 'asc')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('sync_servers')
                    ->label('Sync Servers')
                    ->icon('heroicon-o-link')
                    ->color('success')
                    ->form([
                        Forms\Components\Textarea::make('episode_html')
                            ->label('Episode HTML (opsional)')
                            ->rows(8)
                            ->placeholder('Paste HTML halaman episode untuk parsing tanpa jaringan'),
                        Forms\Components\FileUpload::make('episode_html_file')
                            ->label('Upload Episode HTML (opsional)')
                            ->acceptedFileTypes(['text/html','text/plain'])
                            ->directory('uploads/html-episodes')
                            ->helperText('Alternatif: upload file HTML halaman episode'),
                        Forms\Components\Toggle::make('delete_existing')
                            ->label('Hapus server yang tidak ditemukan')
                            ->helperText('Jika aktif, server lama yang tidak ada di hasil parse akan dihapus'),
                    ])
                    ->action(function (Episode $record, array $data) {
                        $service = app(\App\Services\AnimeSailService::class);

                        // Determine HTML input precedence: pasted > uploaded > none
                        $html = null;
                        if (!empty($data['episode_html'])) {
                            $html = $data['episode_html'];
                        } elseif (!empty($data['episode_html_file'])) {
                            $path = storage_path('app/public/' . $data['episode_html_file']);
                            if (is_file($path)) {
                                $html = file_get_contents($path);
                            }
                        }

                        // HTML is now required
                        if (empty($html)) {
                            \Filament\Notifications\Notification::make()
                                ->title('HTML Required')
                                ->danger()
                                ->body('Mohon paste atau upload HTML halaman episode terlebih dahulu.')
                                ->send();
                            return;
                        }

                        // Parse servers from HTML
                        $servers = $service->getEpisodeServersFromHtml($html);
                        
                        if (empty($servers)) {
                            \Filament\Notifications\Notification::make()
                                ->title('No servers found')
                                ->warning()
                                ->body('Tidak ada video server yang ditemukan dalam HTML ini.')
                                ->send();
                            return;
                        }

                        // Optional delete existing not found
                        if (!empty($data['delete_existing'])) {
                            $keepUrls = collect($servers)->pluck('url')->unique()->values()->all();
                            if (!empty($keepUrls)) {
                                \App\Models\VideoServer::where('episode_id', $record->id)
                                    ->whereNotIn('embed_url', $keepUrls)
                                    ->delete();
                            }
                        }

                        $created = 0; $updated = 0;
                        foreach ($servers as $serverData) {
                            $embedCode = \App\Services\VideoEmbedHelper::toEmbedCode(
                                $serverData['url'],
                                $serverData['name'] ?? null
                            );
                            
                            $vs = \App\Models\VideoServer::updateOrCreate(
                                [
                                    'episode_id' => $record->id,
                                    'embed_url' => $serverData['url'],
                                ],
                                [
                                    'server_name' => $serverData['name'] ?? 'Unknown',
                                    'embed_url' => $embedCode,
                                    'is_active' => true,
                                ]
                            );
                            if ($vs->wasRecentlyCreated) { $created++; } else { $updated++; }
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Sync servers completed')
                            ->success()
                            ->body("Created: {$created} | Updated: {$updated} | Total detected: " . count($servers))
                            ->send();
                    })
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
            'index' => Pages\ListEpisodes::route('/'),
            'create' => Pages\CreateEpisode::route('/create'),
            'edit' => Pages\EditEpisode::route('/{record}/edit'),
        ];
    }    
}
