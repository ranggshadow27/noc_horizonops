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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        function updateClock() {
            const now = new Date();

            // Format date in Indonesian
            const optionsDate = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
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
