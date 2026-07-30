<x-filament-panels::page>
    <div class="flex flex-wrap gap-4 w-full">
        <div class="flex-1 sm:w-1/2 ">
            @livewire('dashboard-grid-charts')
        </div>

        <div class="flex-2 sm:w-1/2">
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>
