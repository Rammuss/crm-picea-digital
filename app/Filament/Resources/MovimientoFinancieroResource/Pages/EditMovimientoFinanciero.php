<?php

namespace App\Filament\Resources\MovimientoFinancieroResource\Pages;

use App\Filament\Resources\MovimientoFinancieroResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMovimientoFinanciero extends EditRecord
{
    protected static string $resource = MovimientoFinancieroResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false),
        ];
    }
}
