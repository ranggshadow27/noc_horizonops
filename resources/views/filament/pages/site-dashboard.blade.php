<x-filament-panels::page>
    {{-- <div>
        <h2>
            <b>NOC Dashboard Cuy~</b>
        </h2>
    </div> --}}

    @livewire(\App\Filament\Resources\SiteMonitorResource\Widgets\SiteMonitorOverview::class)


    <div class="flex flex-wrap justify-center items-center gap-4 w-full">
        <div class="flex-1 sm:w-1/2">
            @livewire(\App\Filament\Widgets\SiteMonitorModemDownByDaysPolarChart::class)
        </div>

        <div class="flex-1 sm:w-1/2">
            @livewire(\App\Filament\Widgets\SiteMonitorRouterDownByDaysPolarChart::class)
        </div>
    </div>

</x-filament-panels::page>
