<x-filament-panels::page>
    <div class="flex justify-between items-center gap-4">
        <div>
            <p class="text-lg font-bold text-center">{{ $record->title }}</p>
            @if ($record->description)
                <p class="mt-12 text-gray-600 text-justify">Description: <br>{{ $record->description }}</p>
            @endif
        </div>
    </div>

    <div class="mt-8">
        @if ($record->file_path)
            <iframe src="{{ asset('storage/' . $record->file_path) }}" width="100%" height="800px" frameborder="0"></iframe>
        @else
            <p>Tidak ada file PDF yang tersedia.</p>
        @endif
    </div>
</x-filament-panels::page>
