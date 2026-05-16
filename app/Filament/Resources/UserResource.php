<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static ?string $navigationLabel = 'Usuarios';
    protected static ?int $navigationSort = 99;

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('Nombre')->required()->maxLength(255),
            Forms\Components\TextInput::make('email')->email()->required()->maxLength(255)->unique(ignoreRecord: true),
            Forms\Components\Select::make('role')
                ->label('Rol')
                ->options([
                    'admin' => 'Admin',
                    'colaborador' => 'Colaborador',
                ])
                ->default('colaborador')
                ->required(),
            Forms\Components\Toggle::make('is_active')->label('Activo')->default(true),
            Forms\Components\Toggle::make('google_auth_enabled')->label('Google habilitado')->default(false),
            Forms\Components\TextInput::make('password')
                ->label('Password')
                ->password()
                ->minLength(8)
                ->required(fn (string $context): bool => $context === 'create')
                ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Hash::make($state) : null)
                ->dehydrated(fn (?string $state): bool => filled($state))
                ->same('password_confirmation'),
            Forms\Components\TextInput::make('password_confirmation')
                ->label('Confirmar password')
                ->password()
                ->required(fn (string $context): bool => $context === 'create')
                ->dehydrated(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nombre')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\BadgeColumn::make('role')->enum([
                    'admin' => 'Admin',
                    'colaborador' => 'Colaborador',
                ])->colors([
                    'danger' => 'admin',
                    'primary' => 'colaborador',
                ]),
                Tables\Columns\IconColumn::make('is_active')->label('Activo')->boolean(),
                Tables\Columns\IconColumn::make('google_auth_enabled')->label('Google')->boolean(),
                Tables\Columns\TextColumn::make('updated_at')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (User $record): bool => $record->id !== auth()->id())
                    ->action(function (User $record): void {
                        /** @var User $current */
                        $current = auth()->user();

                        if ($record->id === $current->id) {
                            Notification::make()
                                ->title('Operacion bloqueada')
                                ->body('No podes eliminarte a vos mismo.')
                                ->danger()
                                ->send();
                            return;
                        }

                        if ($record->role === 'admin' && $record->is_active) {
                            $otherActiveAdmins = User::query()
                                ->where('id', '!=', $record->id)
                                ->where('role', 'admin')
                                ->where('is_active', true)
                                ->count();

                            if ($otherActiveAdmins === 0) {
                                Notification::make()
                                    ->title('Operacion bloqueada')
                                    ->body('Debe quedar al menos un admin activo.')
                                    ->danger()
                                    ->send();
                                return;
                            }
                        }

                        try {
                            $record->delete();
                        } catch (ValidationException $e) {
                            Notification::make()
                                ->title('Operacion bloqueada')
                                ->body(collect($e->errors())->flatten()->first())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->action(function ($records): void {
                        /** @var User $current */
                        $current = auth()->user();
                        $ids = $records->pluck('id')->map(fn ($id): int => (int) $id)->all();

                        if (in_array((int) $current->id, $ids, true)) {
                            Notification::make()
                                ->title('Operacion bloqueada')
                                ->body('No podes eliminarte a vos mismo en borrado masivo.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $activeAdminIds = User::query()
                            ->where('role', 'admin')
                            ->where('is_active', true)
                            ->pluck('id')
                            ->all();

                        $remaining = array_diff($activeAdminIds, $ids);
                        if (count($activeAdminIds) > 0 && count($remaining) === 0) {
                            Notification::make()
                                ->title('Operacion bloqueada')
                                ->body('Debe quedar al menos un admin activo.')
                                ->danger()
                                ->send();
                            return;
                        }

                        try {
                            User::query()->whereIn('id', $ids)->get()->each->delete();
                        } catch (ValidationException $e) {
                            Notification::make()
                                ->title('Operacion bloqueada')
                                ->body(collect($e->errors())->flatten()->first())
                                ->danger()
                                ->send();
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
