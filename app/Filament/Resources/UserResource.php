<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    protected static ?string $navigationGroup = 'User Management';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Akun')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->minLength(8)
                            ->helperText('Kosongkan jika tidak ingin mengubah password'),
                    ])->columns(2),
                
                Forms\Components\Section::make('Profil')
                    ->schema([
                        Forms\Components\FileUpload::make('avatar')
                            ->label('Avatar')
                            ->image()
                            ->directory('avatars')
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('1:1')
                            ->imageResizeTargetWidth('200')
                            ->imageResizeTargetHeight('200'),
                        Forms\Components\Textarea::make('bio')
                            ->label('Bio')
                            ->maxLength(500)
                            ->rows(3),
                        Forms\Components\TextInput::make('phone')
                            ->label('Nomor Telepon')
                            ->tel()
                            ->maxLength(20),
                        Forms\Components\Select::make('gender')
                            ->label('Jenis Kelamin')
                            ->options([
                                'male' => 'Laki-laki',
                                'female' => 'Perempuan',
                            ]),
                        Forms\Components\DatePicker::make('birth_date')
                            ->label('Tanggal Lahir'),
                        Forms\Components\TextInput::make('location')
                            ->label('Lokasi')
                            ->maxLength(255),
                    ])->columns(2),
                
                Forms\Components\Section::make('Role & Status')
                    ->schema([
                        Forms\Components\Select::make('role')
                            ->label('Role')
                            ->options([
                                User::ROLE_USER => 'User',
                                User::ROLE_ADMIN => 'Admin',
                                User::ROLE_SUPERADMIN => 'Superadmin',
                            ])
                            ->default(User::ROLE_USER)
                            ->helperText('Hanya superadmin yang bisa mengubah role.')
                            ->visible(fn () => auth()->user()?->isSuperAdmin())
                            ->required(),
                        Forms\Components\Placeholder::make('role_readonly')
                            ->label('Role')
                            ->content(fn ($record) => ucfirst($record?->role ?? User::ROLE_USER))
                            ->visible(fn () => !auth()->user()?->isSuperAdmin()),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('Avatar')
                    ->circular()
                    ->url(fn ($record) => $record->avatar ? asset('storage/' . $record->avatar) : null)
                    ->getStateUsing(fn ($record) => $record->avatar ? asset('storage/' . $record->avatar) : null),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('role')
                    ->label('Role')
                    ->colors([
                        'gray' => User::ROLE_USER,
                        'success' => User::ROLE_ADMIN,
                        'warning' => User::ROLE_SUPERADMIN,
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_episodes_count')
                    ->label('Episode Dibuat')
                    ->counts('createdEpisodes')
                    ->sortable()
                    ->visible(fn () => auth()->user()?->isSuperAdmin()),
                Tables\Columns\TextColumn::make('admin_episode_logs_sum_amount')
                    ->label('Total Bayaran')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state ?? 0, 0, ',', '.'))
                    ->sortable()
                    ->visible(fn () => auth()->user()?->isSuperAdmin()),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->dateTime('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('comments_count')
                    ->label('Komentar')
                    ->counts('comments')
                    ->sortable(),
                Tables\Columns\TextColumn::make('watch_histories_count')
                    ->label('Riwayat')
                    ->counts('watchHistories')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        User::ROLE_USER => 'User',
                        User::ROLE_ADMIN => 'Admin',
                        User::ROLE_SUPERADMIN => 'Superadmin',
                    ]),
                Tables\Filters\Filter::make('has_avatar')
                    ->label('Punya Avatar')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('avatar')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggleAdmin')
                    ->visible(fn () => auth()->user()?->isSuperAdmin())
                    ->label(fn ($record) => $record->isAdmin() ? 'Turunkan ke User' : 'Jadikan Admin')
                    ->icon(fn ($record) => $record->isAdmin() ? 'heroicon-o-shield-exclamation' : 'heroicon-o-shield-check')
                    ->color(fn ($record) => $record->isAdmin() ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record) => $record->isAdmin() ? 'Hapus Role Admin?' : 'Jadikan Admin?')
                    ->modalSubheading(fn ($record) => $record->isAdmin()
                        ? 'User ini tidak akan bisa mengakses admin panel lagi.'
                        : 'User ini akan mendapat akses ke admin panel.')
                    ->action(function ($record) {
                        if ($record->isSuperAdmin() || $record->id === auth()->id()) {
                            throw new \Exception('Tidak bisa mengubah role superadmin atau akun sendiri.');
                        }

                        $newRole = $record->isAdmin() ? User::ROLE_USER : User::ROLE_ADMIN;
                        $record->update([
                            'role' => $newRole,
                            'is_admin' => $newRole !== User::ROLE_USER,
                        ]);
                    }),
                Tables\Actions\DeleteAction::make()
                    ->before(function ($record) {
                        // Prevent deleting own account
                        if ($record->id === auth()->id()) {
                            throw new \Exception('Tidak bisa menghapus akun sendiri!');
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->before(function ($records) {
                        // Prevent deleting own account in bulk
                        if ($records->contains('id', auth()->id())) {
                            throw new \Exception('Tidak bisa menghapus akun sendiri!');
                        }
                    }),
                Tables\Actions\BulkAction::make('makeAdmin')
                    ->visible(fn () => auth()->user()?->isSuperAdmin())
                    ->label('Jadikan Admin')
                    ->icon('heroicon-o-shield-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn ($records) => $records->each(function ($record) {
                        if ($record->isSuperAdmin()) {
                            return;
                        }
                        $record->update([
                            'role' => User::ROLE_ADMIN,
                            'is_admin' => true,
                        ]);
                    })),
                Tables\Actions\BulkAction::make('removeAdmin')
                    ->visible(fn () => auth()->user()?->isSuperAdmin())
                    ->label('Hapus Role Admin')
                    ->icon('heroicon-o-shield-exclamation')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn ($records) => $records->each(function ($record) {
                        if ($record->id !== auth()->id() && !$record->isSuperAdmin()) {
                            $record->update([
                                'role' => User::ROLE_USER,
                                'is_admin' => false,
                            ]);
                        }
                    })),
            ]);
    }
    
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->user()?->isSuperAdmin()) {
            $query->withCount('createdEpisodes')
                ->withSum('adminEpisodeLogs as admin_episode_logs_sum_amount', 'amount');
        }

        return $query;
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
