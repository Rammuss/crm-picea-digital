<x-filament::page>
    <div class="space-y-4">
        <div class="flex flex-wrap items-center gap-3">
            <div class="w-full md:w-80">
                <input
                    type="text"
                    wire:model.debounce.400ms="buscar"
                    placeholder="Buscar por cliente, codigo o proyecto"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none"
                />
            </div>
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" wire:model="verCerrados">
                <span>Ver cerrados</span>
            </label>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4 2xl:grid-cols-6">
            @foreach ($this->getColumns() as $estado => $column)
                <section class="rounded-xl border border-gray-200 bg-white p-3 shadow-sm">
                    <header class="mb-3 flex items-center justify-between">
                        <h3 class="text-sm font-semibold">{{ $column['label'] }}</h3>
                        <span class="rounded bg-gray-100 px-2 py-1 text-xs">{{ $column['items']->count() }}</span>
                    </header>

                    <div class="space-y-3">
                        @foreach ($column['items'] as $proyecto)
                            <article class="rounded-lg border border-gray-200 p-3">
                                <p class="text-xs text-gray-500">{{ $proyecto->cliente->codigo_cliente ?? '-' }} | {{ $proyecto->cliente->nombre ?? '-' }}</p>
                                <p class="mt-1 text-sm font-semibold">{{ $proyecto->nombre }}</p>
                                <p class="mt-1 text-xs text-gray-600">
                                    Paso: {{ $proyecto->proximo_paso ?: 'Sin definir' }}
                                    @if($proyecto->fecha_proximo_paso)
                                        ({{ $proyecto->fecha_proximo_paso->format('d/m/Y') }})
                                    @endif
                                </p>
                                <p class="mt-1 text-xs text-gray-600">
                                    Saldo: Gs. {{ number_format((float) $proyecto->saldo_pendiente, 0, ',', '.') }}
                                </p>

                                @php($warning = $this->warningText($proyecto))
                                @if($warning)
                                    <p class="mt-2 rounded bg-amber-100 px-2 py-1 text-xs text-amber-900">{{ $warning }}</p>
                                @endif

                                <div class="mt-3 flex flex-wrap gap-1">
                                    @foreach ($this->estados as $stateKey => $stateLabel)
                                        @if($stateKey !== $proyecto->estado)
                                            <button
                                                type="button"
                                                wire:click="moverEstado({{ $proyecto->id }}, '{{ $stateKey }}')"
                                                class="rounded border border-gray-300 px-2 py-1 text-xs hover:bg-gray-50"
                                            >
                                                {{ $stateLabel }}
                                            </button>
                                        @endif
                                    @endforeach
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    </div>
</x-filament::page>
