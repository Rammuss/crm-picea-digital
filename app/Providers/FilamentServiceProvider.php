<?php

namespace App\Providers;

use App\Filament\Pages\PipelineProyectos;
use App\Filament\Widgets\ResumenFinanciero;
use Filament\Facades\Filament;
use Illuminate\Support\ServiceProvider;

class FilamentServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Filament::registerPages([
            PipelineProyectos::class,
        ]);

        Filament::registerWidgets([
            ResumenFinanciero::class,
        ]);
    }
}
