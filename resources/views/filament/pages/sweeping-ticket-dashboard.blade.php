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
    {{-- <div class="flex-1 sm:w-1/2">
        @livewire(\App\Filament\Widgets\SweepingTicketUnWarningTableChart::class)
    </div> --}}

    <div class="flex-1 sm:w-1/2">
        @livewire(\App\Filament\Widgets\SweepingTicketWarningTableChart::class)
    </div>

    <div class="flex-1 sm:w-1/2">
        @livewire(\App\Filament\Widgets\SweepingTicketMinorTableChart::class)
    </div>

    <div class="flex-1 sm:w-1/2">
        @livewire(\App\Filament\Widgets\SweepingTicketMajorTableChart::class)
    </div>
</div>

<div class="flex flex-wrap justify-center items-center gap-4 w-full">
    {{-- <div class="flex-1 sm:w-1/2">
        @livewire(\App\Filament\Widgets\SweepingTTStatusChart::class)
    </div> --}}

    <div class="flex-1 sm:w-1/2">
        @livewire(\App\Filament\Widgets\SweepingTicketWarningTrendChart::class)
    </div>

    <div class="flex-1 sm:w-1/2">
        @livewire(\App\Filament\Widgets\SweepingTicketMinorTrendChart::class)
    </div>

    <div class="flex-1 sm:w-1/2">
        @livewire(\App\Filament\Widgets\SweepingTicketMajorTrendChart::class)
    </div>
</div>




</x-filament-panels::page>
