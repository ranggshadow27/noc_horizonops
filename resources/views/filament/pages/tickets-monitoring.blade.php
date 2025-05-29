<x-filament-panels::page>
    <div class="p-0">
        <!-- Debug jumlah sections -->
        <div class="mb-4 text-gray-600">
            {{-- Debug: {{ count($this->getSections()) }} sections ditemukan --}}
        </div>

        @if (count($this->getSections()) > 0)
            <x-filament::section>
                <x-slot name="heading">
                    NMT Tickets Monitoring
                </x-slot>

                <x-slot name="description">
                    {{ count($this->getSections()) }} Ticket(s) need to be Closed
                </x-slot>

                @foreach ($this->getSections() as $section)
                    {{-- <br> --}}
                    <div class="py-2">
                        <x-filament::section>
                            {{-- <x-slot name="heading">
                                <p class="text-sm font-medium"> {{ $section['site_id'] }} - {{ $section['site_name'] }}</p>
                            </x-slot> --}}

                            {{-- <x-slot name="headerEnd">
                                <x-filament::badge color="success">
                                    Need to Closed
                                </x-filament::badge>
                            </x-slot> --}}

                            <div class="flex gap-6 text-sm md:flex-row md:items-center flex-col inline-block">
                                <div class="flex-2 flex-col">
                                    <p class="text-xs text-gray-600 dark:text-gray-400 py-1">Modem Status : </p>
                                    <x-filament::badge color="success" icon="phosphor-arrow-circle-up-duotone"
                                        class="w-fit px-2 text-sm py-2">
                                        {{ $section['modem_last_up'] }}
                                    </x-filament::badge>
                                </div>

                                <div class="flex-1 flex-col">
                                    {{-- <x-filament::icon
                                            icon="phosphor-ticket-duotone"
                                            class="h-5 w-5 text-xs text-gray-600 dark:text-gray-400" /> --}}
                                    <p class="text-xs text-gray-600 dark:text-gray-400 py-1">Ticket ID :</p>
                                    <p class="font-medium text-gray-800">
                                        {{ $section['ticket_id'] }}</p>
                                </div>

                                <div class="flex-1 flex-col">
                                    <p class="text-xs text-gray-600 dark:text-gray-400 py-1">Site Detail :
                                        {{ $section['site_id'] }}</p>
                                    <p class="font-medium text-gray-800">
                                        {{ $section['site_name'] }}
                                    </p>
                                </div>

                                <div class="flex-1 flex-col">
                                    <p class="text-xs text-gray-600 dark:text-gray-400 py-1">Area / Provinsi :</p>
                                    {{-- <div class="flex gap-2 flex-col py-2"> --}}
                                    <x-filament::badge color="gray" class="w-fit px-2 text-sm py-2 align-start">
                                        {{ $section['area'] }} - {{ $section['province'] }}
                                    </x-filament::badge>

                                    {{-- </div> --}}
                                </div>

                                <div class="flex-1 flex-col">
                                    <p class="text-xs text-gray-600 dark:text-gray-400">Problem Classification :</p>
                                    <p class="font-medium text-gray-800py-1">
                                        {{ $section['problem_classification'] }}</p>
                                </div>

                                <!-- Badge lebih kecil -->

                                <div class="flex-2 flex-col">
                                    <p class="text-xs text-gray-600 dark:text-gray-400 py-1">Ticket Aging :</p>
                                    <x-filament::badge color="danger" icon="phosphor-clock-countdown-duotone"
                                        class="w-fit px-2 py-2 text-sm">
                                        {{ $section['aging'] }}
                                    </x-filament::badge>
                                </div>

                                <div class="flex-2 flex-col">
                                    <p class="text-xs text-gray-600 dark:text-gray-400">Target Online :</p>
                                    <p class="font-medium text-gray-800py-1">
                                        {{ $section['target_online'] }}</p>
                                </div>

                                <div class="flex-2 flex-col">
                                    <p class="text-xs text-gray-600 dark:text-gray-400">Problem Type :</p>
                                    <p class="font-medium text-gray-800py-1">
                                        {{ $section['problem_type'] }}</p>
                                </div>

                            </div>

                        </x-filament::section>
                    </div>
                @endforeach
            </x-filament::section>
        @else
            <x-filament::fieldset>
                Tidak ada data untuk ditampilkan. Cek log untuk detail.
            </x-filament::fieldset>
        @endif
    </div>
</x-filament-panels::page>
