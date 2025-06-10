{{-- <div class="filament-widget p-4 bg-white dark:bg-gray-800 rounded-lg shadow"> --}}
    {{-- <h2 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">Sensor Status Overview</h2> --}}
    <div class="flex flex-wrap justify-between gap-4 w-full">
        <!-- Online Stat -->
        <div class="flex-1 p-4 bg-white dark:bg-gray-900 rounded-lg shadow gap-4">

            <h3 class="text-xs font-bold">Ticket Currently Solved</h3>
            <p class="text-2xl pt-2 font-bold">{{ $this->getData()['online'] }}</p>
            <div class="flex items-center gap-1">
                <p class="text-xs">All sensors are operational</p>
                <x-phosphor-arrow-circle-up-duotone class="w-5 -h-5" />
            </div>
        </div>

        <!-- All Sensor Down Stat -->
        <div class="flex-1 p-4 bg-white dark:bg-gray-900 rounded-lg shadow">
            <h3 class="text-xs font-bold ">All Sensor Down</h3>
            <p class="text-2xl font-bold pt-2">{{ $this->getData()['all_sensor_down'] }}</p>
            <div class="flex items-center gap-1">
                <p class="text-xs">Modem down affecting all sensors</p>
                <x-phosphor-arrow-circle-down-duotone class="w-5 h-5" />
            </div>
        </div>

        <!-- Router Down Stat -->
        <div class="flex-1 p-4 bg-white dark:bg-gray-900 rounded-lg shadow">
            <h3 class="text-xs font-bold ">Router Down</h3>
            <p class="text-2xl font-bold pt-2">{{ $this->getData()['router_down'] }}</p>
             <div class="flex items-center gap-1">
                <p class="text-xs">Router connectivity issue</p>
                <x-phosphor-arrow-circle-down-duotone class="w-5 h-5" />
            </div>
        </div>

        <!-- AP1 Down Stat -->
        <div class="flex-1 p-4 bg-white dark:bg-gray-900 rounded-lg shadow">
            <h3 class="text-xs font-bold ">AP1 Down</h3>
            <p class="text-2xl font-bold pt-2">{{ $this->getData()['ap1_down'] }}</p>
             <div class="flex items-center gap-1">
                <p class="text-xs">Access Point 1 offline</p>
                <x-phosphor-arrow-circle-down-duotone class="w-5 h-5" />
            </div>
        </div>

        <!-- AP2 Down Stat -->
        <div class="flex-1 p-4 bg-white dark:bg-gray-900 rounded-lg shadow">
            <h3 class="text-xs font-bold ">AP2 Down</h3>
            <p class="text-2xl font-bold pt-2">{{ $this->getData()['ap2_down'] }}</p>
             <div class="flex items-center gap-1">
                <p class="text-xs">Access Point 2 offline</p>
                <x-phosphor-arrow-circle-down-duotone class="w-5 h-5" />
            </div>
        </div>

        <!-- AP1&2 Down Stat -->
        <div class="flex-1 p-4 bg-white dark:bg-gray-900 rounded-lg shadow">
            <h3 class="text-xs font-bold ">AP1&2 Down</h3>
            <p class="text-2xl font-bold pt-2">{{ $this->getData()['ap1_and_2_down'] }}</p>
             <div class="flex items-center gap-1">
                <p class="text-xs">Both Access Points offline</p>
                <x-phosphor-arrow-circle-down-duotone class="w-5 h-5" />
            </div>
        </div>
    </div>
{{-- </div> --}}
