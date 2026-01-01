<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdminEpisodeLogResource\Pages;
use App\Models\AdminEpisodeLog;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class AdminEpisodeLogResource extends Resource
{
    protected static ?string $model = AdminEpisodeLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'Admin Performance';
    protected static ?string $navigationGroup = 'Superadmin';
    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function canViewAny($user): bool
    {
        return $user?->isSuperAdmin();
    }

    public static function canCreate($user): bool
    {
        return $user?->isSuperAdmin();
    }

    public static function canEdit($user, $record): bool
    {
        return $user?->isSuperAdmin();
    }

    public static function canDelete($user, $record): bool
    {
        return $user?->isSuperAdmin();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Admin')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('episode_id')
                    ->label('Episode')
                    ->relationship('episode', 'title')
                    ->searchable()
                    ->preload()
                    ->getOptionLabelFromRecordUsing(fn ($record) => 'Ep ' . $record->episode_number . ' - ' . ($record->title ?? ''))
                    ->required(),
                Forms\Components\TextInput::make('amount')
                    ->label('Amount')
                    ->numeric()
                    ->default(AdminEpisodeLog::DEFAULT_AMOUNT)
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        AdminEpisodeLog::STATUS_PENDING => 'Pending',
                        AdminEpisodeLog::STATUS_APPROVED => 'Approved',
                        AdminEpisodeLog::STATUS_PAID => 'Paid',
                    ])
                    ->default(AdminEpisodeLog::STATUS_PENDING)
                    ->required(),
                Forms\Components\Textarea::make('note')
                    ->label('Catatan')
                    ->rows(2)
                    ->nullable(),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Admin')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('episode.title')
                    ->label('Episode')
                    ->formatStateUsing(fn ($record) => 'Ep ' . $record->episode->episode_number . ' - ' . $record->episode->title)
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Bayaran')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => AdminEpisodeLog::STATUS_PENDING,
                        'info' => AdminEpisodeLog::STATUS_APPROVED,
                        'success' => AdminEpisodeLog::STATUS_PAID,
                    ])
                    ->icons([
                        'heroicon-o-clock' => AdminEpisodeLog::STATUS_PENDING,
                        'heroicon-o-check-circle' => AdminEpisodeLog::STATUS_APPROVED,
                        'heroicon-o-currency-dollar' => AdminEpisodeLog::STATUS_PAID,
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        AdminEpisodeLog::STATUS_PENDING => 'Pending',
                        AdminEpisodeLog::STATUS_APPROVED => 'Approved',
                        AdminEpisodeLog::STATUS_PAID => 'Paid',
                    ]),
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Admin')
                    ->relationship('user', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Set Approved')
                    ->icon('heroicon-o-check-circle')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn (AdminEpisodeLog $record) => $record->status === AdminEpisodeLog::STATUS_PENDING)
                    ->action(fn (AdminEpisodeLog $record) => $record->update(['status' => AdminEpisodeLog::STATUS_APPROVED])),
                Tables\Actions\Action::make('markPaid')
                    ->label('Tandai Dibayar')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (AdminEpisodeLog $record) => $record->status !== AdminEpisodeLog::STATUS_PAID)
                    ->action(fn (AdminEpisodeLog $record) => $record->update(['status' => AdminEpisodeLog::STATUS_PAID])),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('markPaid')
                    ->label('Tandai Dibayar')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn ($records) => $records->each->update(['status' => AdminEpisodeLog::STATUS_PAID])),
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user', 'episode.anime']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdminEpisodeLogs::route('/'),
            'create' => Pages\CreateAdminEpisodeLog::route('/create'),
            'edit' => Pages\EditAdminEpisodeLog::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = static::getModel()::where('status', AdminEpisodeLog::STATUS_PENDING)->count();

        return $pending > 0 ? (string) $pending : null;
    }
}
