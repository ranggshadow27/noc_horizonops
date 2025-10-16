<x-filament-panels::page>
    <div class="flex flex-wrap justify-between items-end gap-4 w-full">
        <div>
            <p class="text-xl"><b>Dashboard</b><br>
                <span class="text-gray-400 text-base">Network Operation Center</span>
            </p>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-800"><span id="realTimeClock"></span></p>
        </div>
    </div>

    <div class="flex sm:flex-row flex-col sm:w-1/2 gap-4">
        <div class="flex-1 sm:w-1/2">
            @livewire(\App\Filament\Widgets\NmtTicketsOverview::class)
        </div>

        <div class="flex-1 sm:w-1/2">
            @livewire(\App\Filament\Widgets\SecondNmtTicketsOverview::class)
        </div>
    </div>

    <div class="flex flex-wrap justify-center items-center gap-4 w-full">
        @livewire(\App\Filament\Widgets\NmtTicketSensorClassification::class)
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
        <div class="flex flex-col sm:w-1/2 gap-6">
            {{-- <h2>Sweeping Site</h2> --}}
            <div>
                @livewire(\App\Filament\Widgets\MainDashboardStatFirst::class)
            </div>

            <!-- New Div -->
            <div>
                @livewire(\App\Filament\Widgets\MainDashboardStatSecond::class)
            </div>

            <div>
                @livewire(\App\Filament\Widgets\MainDashboardStatThird::class)
            </div>

            <div>
                @livewire(\App\Filament\Widgets\SweepingTicketMajorTrendChart::class)
            </div>

            {{-- <div>
                @livewire(\App\Filament\Widgets\SweepingTTOverview::class)
            </div> --}}

            {{-- <div>
                @livewire(\App\Filament\Widgets\SweepingTicketWarningTrendChart::class)
            </div> --}}
        </div>
    </div>

    <div class="flex flex-wrap justify-between items-end gap-4 w-full">
        <div>
            <p class="text-xl"><b>CBOSS Trouble Ticket & Maintenance</b><br>
                <span class="text-gray-400 text-base">Network Operation Center</span>
            </p>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-800"><span id="realTimeClock"></span></p>
        </div>
    </div>

    <div class="flex flex-wrap justify-center items-center gap-4 w-full">
        <div class="flex-1 sm:w-1/2">
            @livewire(\App\Filament\Widgets\CbossTTOverview::class)
        </div>

        <div class="flex-1 sm:w-1/2">
            @livewire(\App\Filament\Widgets\SecondCbossTTOverview::class)
        </div>
    </div>

    <div class="flex sm:flex-row flex-col gap-4 space-y-0">
        <!-- Bagian Kiri (Div 1, 2, 4, 5) -->
        <div class="flex flex-col flex-1 sm:w-1/2 gap-4">
            <div class="flex sm:flex-row flex-col sm:w-1/2 gap-4">

                <div class="flex-1 sm:w-1/2">
                    @livewire(\App\Filament\Widgets\TmoLineChart::class)
                </div>

            </div>

            <div class="flex sm:flex-row flex-col sm:w-1/2 gap-4">
                <div class="flex-1 sm:w-1/2">
                    @livewire(\App\Filament\Widgets\CbossTicketByAreaChart::class)
                </div>

                <div class="flex-1 sm:w-1/2">
                    @livewire(\App\Filament\Widgets\CbossTicketDeviceProblem::class)
                </div>

                <div class="flex-1 sm:w-1/2">
                    @livewire(\App\Filament\Widgets\TmoOpenVsClosed::class)
                </div>

            </div>
        </div>
        <!-- Bagian Kanan (Div 3) -->
        <div class="flex-2 sm:w-1/2">
            @livewire(\App\Filament\Widgets\CbossTicketByProvinceTableWidget::class)
        </div>
    </div>

</x-filament-panels::page>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        function updateClock() {
            const now = new Date();

            // Format date in Indonesian
            const optionsDate = {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            };
            const formattedDate = now.toLocaleDateString('id-ID', optionsDate);

            // Get hours, minutes and seconds separately
            const hours = String(now.getHours()).padStart(2, '0'); // Ensure two digits
            const minutes = String(now.getMinutes()).padStart(2, '0'); // Ensure two digits
            const seconds = String(now.getSeconds()).padStart(2, '0'); // Ensure two digits

            // Combine them using ':' as separator for 24-hour format
            const formattedTime = `${hours}:${minutes}:${seconds}`;

            document.getElementById('realTimeClock').textContent = `${formattedDate} - ${formattedTime} WIB`;
        }

        // Update the clock immediately and then every second
        updateClock();
        setInterval(updateClock, 1000);
    });
</script>
