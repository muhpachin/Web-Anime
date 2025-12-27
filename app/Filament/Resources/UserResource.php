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
                        Forms\Components\Toggle::make('is_admin')
                            ->label('Admin')
                            ->helperText('Aktifkan untuk memberikan akses admin panel')
                            ->default(false),
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
                Tables\Columns\IconColumn::make('is_admin')
                    ->label('Admin')
                    ->boolean()
                    ->trueIcon('heroicon-o-shield-check')
                    ->falseIcon('heroicon-o-user')
                    ->trueColor('success')
                    ->falseColor('gray'),
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
                Tables\Filters\TernaryFilter::make('is_admin')
                    ->label('Role')
                    ->placeholder('Semua User')
                    ->trueLabel('Admin Only')
                    ->falseLabel('User Only'),
                Tables\Filters\Filter::make('has_avatar')
                    ->label('Punya Avatar')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('avatar')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggleAdmin')
                    ->label(fn ($record) => $record->is_admin ? 'Remove Admin' : 'Make Admin')
                    ->icon(fn ($record) => $record->is_admin ? 'heroicon-o-shield-exclamation' : 'heroicon-o-shield-check')
                    ->color(fn ($record) => $record->is_admin ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record) => $record->is_admin ? 'Hapus Role Admin?' : 'Jadikan Admin?')
                    ->modalSubheading(fn ($record) => $record->is_admin 
                        ? 'User ini tidak akan bisa mengakses admin panel lagi.' 
                        : 'User ini akan mendapat akses ke admin panel.')
                    ->action(fn ($record) => $record->update(['is_admin' => !$record->is_admin])),
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
                    ->label('Jadikan Admin')
                    ->icon('heroicon-o-shield-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn ($records) => $records->each->update(['is_admin' => true])),
                Tables\Actions\BulkAction::make('removeAdmin')
                    ->label('Hapus Role Admin')
                    ->icon('heroicon-o-shield-exclamation')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn ($records) => $records->each(function ($record) {
                        if ($record->id !== auth()->id()) {
                            $record->update(['is_admin' => false]);
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
