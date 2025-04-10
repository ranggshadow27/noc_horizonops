<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-3">
                <span class="text-lg font-bold">{{ $record->title }}</span>
            </div>
        </x-slot>

        @if ($record->description)
            <x-filament::section.description>
                <div class="space-y-2">
                    <p class="text-gray-600 text-justify">
                        <span class="font-medium">Description:</span>
                        <br>
                        <br>
                        {{ $record->description }}
                    </p>
                </div>
            </x-filament::section.description>
        @endif
    </x-filament::section>

    <x-filament::section class="mt-1">
        <div class="p-1">
            @if ($record->file_path)
                <div class="space-y-4">
                    <x-slot name="heading">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-medium text-gray-900">Document Preview</h3>
                        <a
                            href="{{ asset('storage/' . $record->file_path) }}"
                            target="_blank"
                            class="text-sm text-primary-600 hover:text-primary-800"
                        >
                            Open in New Tab
                        </a>
                    </div>
                    </x-slot>
                    <div class="border rounded-lg overflow-hidden">
                        <iframe
                            src="{{ asset('storage/' . $record->file_path) }}"
                            width="100%"
                            height="800px"
                            frameborder="0"
                            class="w-full"
                        ></iframe>
                    </div>
                </div>
            @else
                <div class="text-center py-8">
                    <x-filament::icon
                        name="heroicon-o-document"
                        class="mx-auto h-12 w-12 text-gray-400"
                    />
                    <p class="mt-2 text-sm text-gray-600">
                        Tidak ada file PDF yang tersedia.
                    </p>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-panels::page>