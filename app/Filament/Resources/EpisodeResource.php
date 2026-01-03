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
use Illuminate\Support\Facades\Storage; // <--- PENTING: Import Library Storage
use App\Models\VideoServer;

class EpisodeResource extends Resource
{
    protected static ?string $model = Episode::class;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // --- BAGIAN 1: DATA EPISODE (DATA LAMA) ---
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('episode_number')
                            ->required()
                            ->numeric()
                            ->label('Nomor Episode'),
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set, $state) => $set('slug', Str::slug($state)))
                            ->label('Judul Episode'),
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Auto-generated dari title'),
                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi'),
                        Forms\Components\Select::make('anime_id')
                            ->relationship('anime', 'title')
                            ->searchable()
                            ->preload()
                            ->placeholder('Cari & pilih anime')
                            ->required(),
                    ]),

                // --- BAGIAN 2: VIDEO SERVERS MANAGER (FITUR BARU UNTUK ADMIN) ---
                Forms\Components\Section::make('Video Servers (Manual & Upload)')
                    ->description('Kelola link video manual atau paste nama file dari FileBrowser (Upload Center).')
                    ->schema([
                        Forms\Components\Repeater::make('videoServers')
                            ->relationship()
                            ->schema([
                                Forms\Components\TextInput::make('server_name')
                                    ->label('Nama Server')
                                    ->default('Server Admin')
                                    ->required(),

                                // KOLOM PINTAR: Paste Nama File Disini
                                Forms\Components\TextInput::make('manual_filename')
                                    ->label('Paste Nama File Video')
                                    ->placeholder('Contoh: naruto-ep1.mp4')
                                    ->helperText('Ambil nama file dari FileBrowser (Port :8081).')
                                    ->dehydrated(false) // Tidak disimpan ke database, cuma alat bantu
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        // LOGIC OTOMATIS: Ubah nama file jadi URL
                                        if ($state) {
                                            // Asumsi file ada di folder 'videos/episodes/'
                                            // Pastikan admin upload ke folder 'videos/episodes' di FileBrowser
                                            $url = Storage::disk('public')->url('videos/episodes/' . $state);
                                            $set('embed_url', $url);
                                        }
                                    })
                                    // TOMBOL PINTAS KE FILEBROWSER
                                    ->suffixAction(
                                        Forms\Components\Actions\Action::make('open_filebrowser')
                                            ->icon('heroicon-o-external-link')
                                            ->url('http://192.168.100.13:8081', true) // IP FileBrowser
                                            ->tooltip('Buka FileBrowser Upload Center')
                                    ),

                                Forms\Components\TextInput::make('embed_url')
                                    ->label('URL Video Final')
                                    ->required()
                                    ->columnSpan('full')
                                    ->helperText('Terisi otomatis jika mengisi nama file di atas.'),
                                
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Aktif')
                                    ->default(true),
                            ])
                            ->createItemButtonLabel('Tambah Server Manual')
                            ->defaultItems(0)
                            ->columns(2)
                    ])
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
                
                // ACTION UPLOAD LOKAL (JANGAN DIUBAH - SUDAH WORK)
                Tables\Actions\Action::make('upload_local')
                    ->label('Upload Video Lokal')
                    ->icon('heroicon-o-upload')
                    ->color('primary')
                    ->form([
                        Forms\Components\View::make('filament.components.upload-progress')
                            ->viewData(['target' => 'video_files']),
                        Forms\Components\TextInput::make('server_name')
                            ->label('Nama Server')
                            ->default('Server Admin 720p')
                            ->required(),
                        Forms\Components\FileUpload::make('video_files')
                            ->label('File Video (MP4)')
                            ->multiple()
                            ->required()
                            ->directory('videos/episodes')
                            ->disk('public')
                            ->preserveFilenames()
                            ->acceptedFileTypes(['video/mp4'])
                            ->helperText('Bisa pilih banyak file; setiap file jadi satu server otomatis.'),
                    ])
                    ->action(function (Episode $record, array $data) {
                        $files = $data['video_files'] ?? [];
                        if (empty($files)) {
                            \Filament\Notifications\Notification::make()
                                ->title('Upload gagal')
                                ->danger()
                                ->body('File video wajib diisi.')
                                ->send();
                            return;
                        }

                        $serverName = $data['server_name'] ?? 'Server Admin 720p';
                        $created = 0; $updated = 0;

                        foreach ($files as $filePath) {
                            $url = Storage::disk('public')->url($filePath);
                            $quality = null;
                            if (preg_match('/(1080|720|480|360)p/i', $filePath, $m)) {
                                $quality = $m[1] . 'p';
                            }
                            $name = $serverName;
                            if ($quality && stripos($serverName, $quality) === false) {
                                $name = $serverName . ' ' . $quality;
                            }

                            $vs = VideoServer::updateOrCreate(
                                [
                                    'episode_id' => $record->id,
                                    'server_name' => $name,
                                ],
                                [
                                    'embed_url' => $url,
                                    'is_active' => true,
                                ]
                            );

                            if ($vs->wasRecentlyCreated) { $created++; } else { $updated++; }
                        }

                        // Admin log when a local upload happens
                        $user = auth()->user();
                        if ($user && $user->isAdmin()) {
                            \App\Models\AdminEpisodeLog::updateOrCreate(
                                [
                                    'user_id' => $user->id,
                                    'episode_id' => $record->id,
                                ],
                                [
                                    'amount' => \App\Models\AdminEpisodeLog::DEFAULT_AMOUNT,
                                    'status' => \App\Models\AdminEpisodeLog::STATUS_PENDING,
                                    'note' => 'Upload video internal (' . $serverName . ')',
                                ]
                            );
                        }

                        \Filament\Notifications\Notification::make()
                                ->title('Upload berhasil')
                                ->success()
                            ->body('Server ditambahkan: ' . ($created + $updated) . ' entri')
                            ->send();
                    }),
                
                // ACTION SYNC SERVERS (JANGAN DIUBAH - SUDAH WORK)
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
                            ->preserveFilenames()
                            ->helperText('Alternatif: upload file HTML halaman episode'),
                        Forms\Components\Toggle::make('delete_existing')
                            ->label('Hapus server yang tidak ditemukan')
                            ->helperText('Jika aktif, server lama yang tidak ada di hasil parse akan dihapus'),
                    ])
                    ->action(function (Episode $record, array $data) {
                        $service = app(\App\Services\AnimeSailService::class);

                        $html = null;
                        if (!empty($data['episode_html'])) {
                            $html = $data['episode_html'];
                        } elseif (!empty($data['episode_html_file'])) {
                            $path = storage_path('app/public/' . $data['episode_html_file']);
                            if (is_file($path)) {
                                $html = file_get_contents($path);
                            }
                        }

                        if (empty($html)) {
                            \Filament\Notifications\Notification::make()
                                ->title('HTML Required')
                                ->danger()
                                ->body('Mohon paste atau upload HTML halaman episode terlebih dahulu.')
                                ->send();
                            return;
                        }

                        $servers = $service->getEpisodeServersFromHtml($html);
                        
                        if (empty($servers)) {
                            \Filament\Notifications\Notification::make()
                                ->title('No servers found')
                                ->warning()
                                ->body('Tidak ada video server yang ditemukan dalam HTML ini.')
                                ->send();
                            return;
                        }

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

                        // --- AUTO CREATE ADMIN LOG (SETIAP SYNC) ---
                        $user = auth()->user();
                        if ($user && $user->isAdmin() && ($created > 0 || $updated > 0)) {
                            \App\Models\AdminEpisodeLog::updateOrCreate(
                                [
                                    'user_id' => $user->id,
                                    'episode_id' => $record->id,
                                ],
                                [
                                    'amount' => \App\Models\AdminEpisodeLog::DEFAULT_AMOUNT,
                                    'status' => \App\Models\AdminEpisodeLog::STATUS_PENDING,
                                    'note' => "Sync video servers (Created: {$created}, Updated: {$updated})",
                                ]
                            );
                        }

                        // --- AUTO CLEANUP SINGLE UPLOAD ---
                        // Hapus file setelah selesai diproses
                        if (!empty($data['episode_html_file'])) {
                            Storage::disk('public')->delete($data['episode_html_file']);
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
                
                // BULK UPLOAD LOCAL (JANGAN DIUBAH)
                Tables\Actions\BulkAction::make('bulk_upload_local')
                    ->label('Bulk Upload Video Lokal')
                    ->icon('heroicon-o-upload')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\View::make('filament.components.upload-progress')
                            ->viewData(['target' => 'video_files']),
                        Forms\Components\TextInput::make('server_name')
                            ->label('Nama Server')
                            ->default('Server Admin 720p')
                            ->required(),
                        Forms\Components\FileUpload::make('video_files')
                            ->label('File Video (MP4)')
                            ->multiple()
                            ->required()
                            ->directory('videos/episodes')
                            ->disk('public')
                            ->preserveFilenames()
                            ->acceptedFileTypes(['video/mp4'])
                            ->helperText('Map otomatis ke episode berdasar nomor di nama file (contoh: Ep 3, episode-04).'),
                    ])
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                        $files = $data['video_files'] ?? [];
                        if (empty($files)) {
                            \Filament\Notifications\Notification::make()
                                ->title('Upload gagal')
                                ->danger()
                                ->body('File video wajib diisi.')
                                ->send();
                            return;
                        }

                        $serverName = $data['server_name'] ?? 'Server Admin 720p';
                        $map = [];
                        $unmapped = 0;

                        foreach ($files as $filePath) {
                            $filename = urldecode(basename($filePath));
                            $epNum = null;
                            if (preg_match('/(?:Episode|Ep)[^0-9]*(\d+)/i', $filename, $m)) {
                                $epNum = (int) $m[1];
                            } elseif (preg_match('/[\-_](\d+)\.(?:mp4)$/i', $filename, $m)) {
                                $epNum = (int) $m[1];
                            }

                            if ($epNum) {
                                $map[$epNum][] = $filePath;
                            } else {
                                $unmapped++;
                            }
                        }

                        $created = 0; $updated = 0; $skipped = 0;
                        $recordsList = $records->sortBy('episode_number')->values();

                        foreach ($recordsList as $episode) {
                            $epNum = $episode->episode_number;
                            if (!isset($map[$epNum]) || empty($map[$epNum])) {
                                $skipped++;
                                continue;
                            }

                            foreach ($map[$epNum] as $filePath) {
                                $url = \Storage::disk('public')->url($filePath);
                                $quality = null;
                                if (preg_match('/(1080|720|480|360)p/i', $filePath, $m)) {
                                    $quality = $m[1] . 'p';
                                }

                                $name = $serverName;
                                if ($quality && stripos($serverName, $quality) === false) {
                                    $name = $serverName . ' ' . $quality;
                                }

                                $vs = \App\Models\VideoServer::updateOrCreate(
                                    [
                                        'episode_id' => $episode->id,
                                        'server_name' => $name,
                                    ],
                                    [
                                        'embed_url' => $url,
                                        'is_active' => true,
                                    ]
                                );

                                if ($vs->wasRecentlyCreated) { $created++; } else { $updated++; }
                            }
                        }

                        $body = "Created: {$created} | Updated: {$updated}";
                        if ($skipped > 0) {
                            $body .= " | Skipped (no match): {$skipped}";
                        }
                        if ($unmapped > 0) {
                            $body .= " | File tak ter-mapping: {$unmapped}";
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Bulk upload selesai')
                            ->success()
                            ->body($body)
                            ->send();
                    }),

                // BULK SYNC SERVERS (JANGAN DIUBAH - SUDAH WORK)
                Tables\Actions\BulkAction::make('bulk_sync_servers')
                    ->label('Bulk Sync Servers')
                    ->icon('heroicon-o-refresh')
                    ->color('success')
                    ->form([
                        Forms\Components\Textarea::make('html_content')
                            ->label('HTML Content (untuk semua episode)')
                            ->rows(6)
                            ->placeholder('Paste HTML halaman yang berisi video servers...')
                            ->helperText('Opsional: Paste HTML yang sama untuk semua episode yang dipilih'),
                        Forms\Components\FileUpload::make('html_files')
                            ->label('Upload HTML Files (per episode)')
                            ->multiple()
                            ->acceptedFileTypes(['text/html', 'text/plain', '.html', '.htm', '.txt'])
                            ->directory('uploads/bulk-html') // Folder sementara
                            ->preserveFilenames()
                            ->helperText('Upload file HTML. Sistem akan membaca, sinkronisasi, lalu MENGHAPUS file otomatis agar hemat memori.'),
                        Forms\Components\Toggle::make('delete_existing')
                            ->label('Hapus server lama yang tidak ditemukan')
                            ->default(false),
                    ])
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                        $service = app(\App\Services\AnimeSailService::class);
                        $globalHtml = $data['html_content'] ?? '';
                        $htmlFiles = $data['html_files'] ?? [];
                        
                        $episodeHtmlMap = [];
                        
                        // --- 1. BACA & MAPPING FILE ---
                        foreach ($htmlFiles as $file) {
                            $path = storage_path('app/public/' . $file);
                            
                            if (is_file($path)) {
                                $filename = urldecode(basename($file));
                                $content = file_get_contents($path);
                                
                                // Regex Cerdas
                                if (preg_match('/(?:Episode|Ep)[^0-9]*(\d+)/i', $filename, $matches)) {
                                    $epNum = (int) $matches[1];
                                    $episodeHtmlMap[$epNum] = $content;
                                } elseif (preg_match('/[\s\-_](\d+)\.(?:html|txt|htm)$/i', $filename, $matches)) {
                                    $epNum = (int) $matches[1];
                                    $episodeHtmlMap[$epNum] = $content;
                                }
                            }
                        }
                        
                        if (empty($globalHtml) && empty($episodeHtmlMap)) {
                            \Filament\Notifications\Notification::make()
                                ->title('File Tidak Terbaca')
                                ->danger()
                                ->body('Tidak ada file HTML yang cocok dengan nomor episode.')
                                ->send();
                            return;
                        }
                        
                        $totalCreated = 0;
                        $totalUpdated = 0;
                        $processedEpisodes = 0;
                        $skippedEpisodes = 0;
                        
                        $recordsList = $records->sortBy('episode_number')->values();
                        
                        // --- 2. PROSES DATA KE DATABASE ---
                        foreach ($recordsList as $episode) {
                            $html = null;
                            $epNum = $episode->episode_number;

                            if (isset($episodeHtmlMap[$epNum])) {
                                $html = $episodeHtmlMap[$epNum];
                            } elseif (!empty($globalHtml)) {
                                $html = $globalHtml;
                            }
                            
                            if (empty($html)) {
                                $skippedEpisodes++;
                                continue;
                            }
                            
                            $servers = $service->getEpisodeServersFromHtml($html);
                            if (empty($servers)) {
                                $skippedEpisodes++;
                                continue;
                            }
                            
                            if (!empty($data['delete_existing'])) {
                                $keepUrls = collect($servers)->pluck('url')->unique()->values()->all();
                                if (!empty($keepUrls)) {
                                    \App\Models\VideoServer::where('episode_id', $episode->id)
                                        ->whereNotIn('embed_url', $keepUrls)
                                        ->delete();
                                }
                            }
                            
                            foreach ($servers as $serverData) {
                                $embedCode = \App\Services\VideoEmbedHelper::toEmbedCode(
                                    $serverData['url'],
                                    $serverData['name'] ?? null
                                );
                                
                                $vs = \App\Models\VideoServer::updateOrCreate(
                                    [
                                        'episode_id' => $episode->id,
                                        'embed_url' => $serverData['url'],
                                    ],
                                    [
                                        'server_name' => $serverData['name'] ?? 'Unknown',
                                        'embed_url' => $embedCode,
                                        'is_active' => true,
                                    ]
                                );
                                if ($vs->wasRecentlyCreated) { $totalCreated++; } else { $totalUpdated++; }
                            }
                            $processedEpisodes++;
                            
                            // --- AUTO CREATE ADMIN LOG PER EPISODE ---
                            $user = auth()->user();
                            if ($user && $user->isAdmin() && !empty($servers)) {
                                \App\Models\AdminEpisodeLog::updateOrCreate(
                                    [
                                        'user_id' => $user->id,
                                        'episode_id' => $episode->id,
                                    ],
                                    [
                                        'amount' => \App\Models\AdminEpisodeLog::DEFAULT_AMOUNT,
                                        'status' => \App\Models\AdminEpisodeLog::STATUS_PENDING,
                                        'note' => "Bulk sync video servers (" . count($servers) . " servers)",
                                    ]
                                );
                            }
                        }

                        // --- 3. AUTO CLEANUP (FITUR FILE SAMPAH) ---
                        // Hapus semua file yang baru saja diupload dari disk
                        foreach ($htmlFiles as $file) {
                            if (Storage::disk('public')->exists($file)) {
                                Storage::disk('public')->delete($file);
                            }
                        }

                        $message = "Processed: {$processedEpisodes} | Created: {$totalCreated} | Updated: {$totalUpdated}";
                        if ($skippedEpisodes > 0) {
                            $message .= " | Skipped: {$skippedEpisodes}";
                        }
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Bulk Sync Completed & Cleaned Up')
                            ->success()
                            ->body($message . " (File sampah telah dihapus)")
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion()
                    ->requiresConfirmation()
                    ->modalHeading('Bulk Sync Video Servers')
                    ->modalSubheading('Upload file -> Proses -> File otomatis dihapus setelah selesai.'),
            ]);
    }
    
    public static function getRelations(): array
    {
        return [];
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