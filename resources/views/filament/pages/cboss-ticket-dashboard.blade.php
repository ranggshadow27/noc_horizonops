<x-filament-panels::page>

<div class="flex flex-wrap justify-center items-center gap-4 w-full">
    <div class="flex-1 sm:w-1/2">
        @livewire(\App\Filament\Widgets\CBOSSTicketOverview::class)
    </div>

    <div class="flex-1 sm:w-1/2">
        @livewire(\App\Filament\Widgets\SecondCBOSSTicketOverview::class)
    </div>
</div>

    <div class="flex flex-wrap justify-center items-center gap-4 w-full">
        <div class="flex-1 sm:w-1/2">
            @livewire(\App\Filament\Widgets\CbossTicketStatusOverview::class)
        </div>

        <div class="flex-1 sm:w-1/2">
            @livewire(\App\Filament\Widgets\CbossTicketProblemDetailLineChart::class)
        </div>
    </div>

    <div class="flex sm:flex-row flex-col gap-4 space-y-0">
        <!-- Bagian Kiri (Div 1, 2, 4, 5) -->
        <div class="flex flex-col flex-1 sm:w-1/2 gap-4">
            <!-- Div 1 -->
            <div class="flex sm:flex-row flex-col sm:w-1/2 gap-4">
                <div class="flex-1 sm:w-1/2">
                    @livewire(\App\Filament\Widgets\CbossTicketOpenVsClosedChart::class)
                </div>
                <!-- Div 2 -->
                <div class="flex-1 sm:w-1/2">
                    @livewire(\App\Filament\Widgets\CbossTicketByProblemClassChart::class)
                </div>
            </div>

            <div class="flex sm:flex-row flex-col sm:w-1/2 gap-4">
                <!-- Div 4 -->
                <div class="flex-1 sm:w-1/2">
                    @livewire(\App\Filament\Widgets\CbossTicketByAreaChart::class)
                </div>
                <!-- Div 5 -->
                <div class="flex-1 sm:w-1/2">
                    @livewire(\App\Filament\Widgets\CbossTicketOpenDurationChart::class)
                </div>


            </div>
        </div>
        <!-- Bagian Kanan (Div 3) -->
        <div class="flex-2 sm:w-1/2">
            @livewire(\App\Filament\Widgets\CbossTicketByProvinceTableWidget::class)
        </div>
    </div>

</x-filament-panels::page>
