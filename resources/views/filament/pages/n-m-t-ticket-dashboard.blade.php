<x-filament-panels::page>
    <div>
        <h2>
            <b>NOC Dashboard Cuy~</b>
        </h2>
    </div>

    <div class="flex flex-wrap justify-center items-center gap-4 w-full">
        <!-- Kolom 1 -->
        <div class="flex-1 sm:w-1/2">
            @livewire(\App\Filament\Widgets\NmtTicketProblemDetailLineChart::class)
        </div>

        {{-- <div class="flex-1 sm:w-1/2">
            @livewire(\App\Filament\Widgets\NmtTicketByProblemClassChart::class)
        </div> --}}

    </div>

    <div class="flex flex-wrap justify-center items-center gap-4 w-full">
        <div class="flex-1 sm:w-1/2">
            @livewire(\App\Filament\Widgets\NmtTicketsOverview::class)
        </div>

        <div class="flex-1 sm:w-1/2">
            @livewire(\App\Filament\Widgets\SecondNmtTicketsOverview::class)
        </div>
    </div>

    <div class="flex flex-wrap justify-center items-center gap-4 w-full">
        <!-- Kolom 1 -->
        <div class="flex-1 sm:w-1/2">
            @livewire(\App\Filament\Widgets\NmtTicketStatusOverview::class)
        </div>

        <div class="flex-1 sm:w-1/2">
            @livewire(\App\Filament\Widgets\NmtTicketByProblemClassChart::class)
        </div>



        {{-- <div class="flex-1 sm:w-1/2">
            @livewire(\App\Filament\Widgets\NmtTicketByAreaChart::class)
        </div> --}}
    </div>

    <div class="flex flex-wrap justify-center items-center gap-4 w-full">
        <!-- Kolom 1 -->
        <div class="flex-1 sm:w-1/2">
            <!-- Kolom kedua bisa kosong atau diisi konten lain -->
            @livewire(\App\Filament\Widgets\NmtTicketOpenVsClosedChart::class)
        </div>

        <div class="flex-1 sm:w-1/2">
            @livewire(\App\Filament\Widgets\NmtTicketByAreaChart::class)
        </div>

        <div class="flex-1 sm:w-1/2">
            @livewire(\App\Filament\Widgets\NmtTicketsByProvinceTableWidget::class)
        </div>
    </div>

</x-filament-panels::page>
