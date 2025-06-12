{{-- <div class="filament-widget p-4 bg-white dark:bg-gray-800 rounded-lg shadow"> --}}
{{-- <h2 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">Sensor Status Overview</h2> --}}
<div class="w-full sm:flex-1 grid gap-4">
    <div class="sm:flex flex-warp justify-between gap-6 w-full">
        <!-- All Sensor Down Stat -->
        <div class="flex-1 mb-4">
            <x-filament::section>
                <div class="grid items-center gap-1 p-0">
                    {{-- <p class="text-xs">Modem down affecting all sensors</p> --}}
                    <p style="--c-400:var(--danger-400);--c-500:var(--danger-500);"
                        class="fi-wi-stats-overview-stat-value fi-color-custom text-custom-500 dark:text-custom-400 text-2xl font-bold fi-color-warning">
                        {{ $this->getData()['all_sensor_down'] }} <span class="text-sm">Ticket(s)</span>
                    </p>
                    <div class="flex gap-2 items-center">
                        <h3 class="text-xs">Sensors currently down</h3>
                        <x-phosphor-arrow-circle-down-duotone class="w-5 h-5" />
                    </div>
                </div>
            </x-filament::section>
        </div>

        <!-- Online Stat -->
        <div class="flex-1 mb-4">
            <x-filament::section>
                <div class="grid items-center gap-1">
                    <p style="--c-400:var(--warning-400);--c-500:var(--warning-500);"
                        class="fi-wi-stats-overview-stat-value fi-color-custom text-custom-500 dark:text-custom-400 text-2xl font-bold fi-color-warning">
                        {{ $this->getData()['online'] }} <span class="text-sm">Ticket(s)</span>
                    </p>
                    <div class="flex gap-2 items-center">
                        <p class="text-xs">Sensor problem behind Modem</p>
                        <x-phosphor-arrow-circle-down-duotone class="w-5 -h-5" />
                    </div>
                </div>
            </x-filament::section>
        </div>


        <!-- Router Down Stat -->
        <div class="flex-1 mb-4">
            <x-filament::section>
                <div class="grid items-center gap-1">
                    <p style="--c-400:var(--warning-400);--c-500:var(--warning-500);"
                        class="fi-wi-stats-overview-stat-value fi-color-custom text-custom-500 dark:text-custom-400 text-2xl font-bold fi-color-warning">
                        {{ $this->getData()['router_down'] }} <span class="text-sm">Ticket(s)</span>
                    </p>
                    <div class="flex gap-2 items-center">
                        <p class="text-xs">Router connectivity issue</p>
                        <x-phosphor-arrow-circle-down-duotone class="w-5 h-5" />
                    </div>
                </div>
            </x-filament::section>
        </div>



        <!-- AP1 Down Stat -->
        <div class="flex-1 mb-4">
            <x-filament::section>
                <div class="grid items-center gap-1">
                    <p style="--c-400:var(--gray-400);--c-600:var(--gray-600);"
                        class="fi-wi-stats-overview-stat-value fi-color-custom text-custom-600 dark:text-custom-400 text-2xl font-bold fi-color-warning">
                        {{ $this->getData()['ap1_down'] }} <span class="text-xs">AP1</span> /
                        {{ $this->getData()['ap2_down'] }} <span class="text-xs">AP2</span> <span class="text-sm">Ticket(s)</span>
                    </p>
                    <div class="flex gap-2 items-center">
                        <p class="text-xs">Access Point 1 / 2 offline</p>
                        <x-phosphor-arrow-circle-down-duotone class="w-5 h-5" />
                    </div>
                </div>
            </x-filament::section>
        </div>

        <!-- AP1&2 Down Stat -->
        <div class="flex-1 mb-4">
            <x-filament::section>
                <div class="grid items-center gap-1">
                    <p style="--c-400:var(--warning-400);--c-500:var(--warning-500);"
                        class="fi-wi-stats-overview-stat-value fi-color-custom text-custom-500 dark:text-custom-400 text-2xl font-bold fi-color-warning">
                        {{ $this->getData()['ap1_and_2_down'] }} <span class="text-sm">Ticket(s)</span>
                    </p>
                    <div class="flex gap-2 items-center">
                        <p class="text-xs">Both Access Points offline</p>
                        <x-phosphor-arrow-circle-down-duotone class="w-5 h-5" />
                    </div>
                </div>
            </x-filament::section>
        </div>

        <div class="flex-1 mb-4">
            <!-- Aging Stat -->
            <x-filament::section>
                <div class="grid items-center gap-1">
                    <p style="--c-400:var(--gray-400);--c-600:var(--gray-600);"
                        class="fi-wi-stats-overview-stat-value fi-color-custom text-custom-600 dark:text-custom-400 text-2xl font-bold fi-color-warning">
                        {{ $this->getData()['aging'] }} <span class="text-sm">day(s)</span>
                    </p>
                    <div class="flex gap-2 items-center">
                        <p class="text-xs">Aging with non-Modem Problem</p>
                        <x-phosphor-clock-countdown-duotone class="w-5 h-5" />
                    </div>
                </div>
            </x-filament::section>
        </div>
    </div>
</div>
{{-- </div> --}}
