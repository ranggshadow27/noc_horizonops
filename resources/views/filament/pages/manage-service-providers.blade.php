<x-filament-panels::page>
    <div class="flex flex-wrap lg:flex-nowrap gap-4 w-full">
        <!-- Berikan class min-w-0 agar flex child tidak melebar melampaui ukurannya -->
        <div class="flex-1 lg:w-1/2 min-w-0">
            @livewire('dashboard-grid-charts')
        </div>

        <div class="flex-2 lg:w-1/2 min-w-0">
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>
