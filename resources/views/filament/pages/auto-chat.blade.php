<x-filament-panels::page>
    <div class="flex flex-col min-h-screen">
        <!-- Konten utama -->
        <div class="flex-grow">
            {{ $this->form }}
        </div>

        <div class="hidden flex-col sm:flex w-full items-center gap-2 -z-100">
            <br>
            <p class="text-gray-400 text-sm antialiased">
                &copy;{{ date('Y') }}. Mahaga <b>Network Operation Center</b>.
                            </p>
            <p class="text-gray-200 text-sm font-semibold antialiased">
                - ratipray27
            </p>
        </div>
    </div>
</x-filament-panels::page>
