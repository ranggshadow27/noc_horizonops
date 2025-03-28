<x-filament-panels::page>


<div class="flex flex-wrap justify-center items-center gap-4 w-full">
    <div class="flex-1 sm:w-1/2">
        @livewire(\App\Filament\Widgets\SweepingTTOverview::class)
    </div>

    <div class="flex-1 sm:w-1/2">
        @livewire(\App\Filament\Widgets\SecondSweepingTTOverview::class)
    </div>
</div>

<div class="flex flex-wrap justify-center items-center gap-4 w-full">
    <div class="flex-1 sm:w-1/2">
        @livewire(\App\Filament\Widgets\SweepingTTStatusChart::class)
    </div>

    {{-- <div class="flex-1 sm:w-1/2">
        @livewire(\App\Filament\Widgets\SecondSweepingTTOverview::class)
    </div> --}}
</div>


</x-filament-panels::page>
