<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getActions(): array
    {
        return [];
    }

    protected function beforeSave(): void
    {
        /** @var User $current */
        $current = auth()->user();
        /** @var User $record */
        $record = $this->record;
        $data = $this->form->getState();
        $willBeAdmin = ($data['role'] ?? $record->role) === 'admin';
        $willBeActive = (bool) ($data['is_active'] ?? $record->is_active);

        if ($record->id === $current->id && $record->role === 'admin' && ! $willBeAdmin) {
            $otherActiveAdmins = User::query()
                ->where('id', '!=', $record->id)
                ->where('role', 'admin')
                ->where('is_active', true)
                ->count();

            if ($otherActiveAdmins === 0) {
                Notification::make()
                    ->title('Operacion bloqueada')
                    ->body('No podes pasarte a colaborador si sos el unico admin activo.')
                    ->danger()
                    ->send();
                $this->halt();
                return;
            }
        }

        if ($record->id === $current->id && $record->is_active && ! $willBeActive) {
            Notification::make()
                ->title('Operacion bloqueada')
                ->body('No podes desactivarte a vos mismo.')
                ->danger()
                ->send();
            $this->halt();
            return;
        }

        if ($record->role === 'admin' && (! $willBeAdmin || ! $willBeActive)) {
            $otherActiveAdmins = User::query()
                ->where('id', '!=', $record->id)
                ->where('role', 'admin')
                ->where('is_active', true)
                ->count();

            if ($otherActiveAdmins === 0) {
                Notification::make()
                    ->title('Operacion bloqueada')
                    ->body('Debe quedar al menos un admin activo en el sistema.')
                    ->danger()
                    ->send();
                $this->halt();
                return;
            }
        }
    }

}
