<x-filament-panels::page>
    {{-- <div>
        <h2>
            <b>NOC Dashboard Cuy~</b>
        </h2>
    </div> --}}

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
            @livewire(\App\Filament\Widgets\NmtTicketProblemDetailLineChart::class)
        </div>

    </div>

    <div class="flex sm:flex-row flex-col gap-4 space-y-0">
        <!-- Bagian Kiri (Div 1, 2, 4, 5) -->
        <div class="flex flex-col flex-1 sm:w-1/2 gap-4">
            <!-- Div 1 -->
            <div class="flex sm:flex-row flex-col sm:w-1/2 gap-4">
                <div class="flex-1 sm:w-1/2">
                    @livewire(\App\Filament\Widgets\NmtTicketOpenVsClosedChart::class)
                </div>
                <!-- Div 2 -->
                <div class="flex-1 sm:w-1/2">
                    @livewire(\App\Filament\Widgets\NmtTicketByProblemClassChart::class)
                </div>
            </div>

            <div class="flex sm:flex-row flex-col sm:w-1/2 gap-4">
                <!-- Div 4 -->
                <div class="flex-1 sm:w-1/2">
                    @livewire(\App\Filament\Widgets\NmtTicketByAreaChart::class)
                </div>
                <!-- Div 5 -->
                <div class="flex-1 sm:w-1/2">
                    @livewire(\App\Filament\Widgets\NmtTicketOpenDurationChart::class)
                </div>

                <div class="flex-1 sm:w-1/2">
                    @livewire(\App\Filament\Widgets\NmtTicketTeknisNonTeknisChart::class)
                </div>
            </div>
        </div>
        <!-- Bagian Kanan (Div 3) -->
        <div class="flex-2 sm:w-1/2">
            @livewire(\App\Filament\Widgets\NmtTicketsByProvinceTableWidget::class)
        </div>
    </div>

</x-filament-panels::page>
