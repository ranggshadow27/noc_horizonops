@php
    use Carbon\Carbon;
@endphp
<div class="flex flex-col gap-4">
    @if (isset($record) && $record && $record->comments && is_array($record->comments))
        @php
            // Sort comments descending berdasarkan time
            $sortedComments = $record->comments;
            usort($sortedComments, function ($a, $b) {
                return strtotime($b['time']) <=> strtotime($a['time']);
            });
        @endphp
        @foreach ($sortedComments as $comment)
            <div
                class="flex flex-col p-4 fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 hover:bg-gray-100">
                <div class="flex flex-row items-start gap-2">
                    <div class="flex">
                        <p class="text-sm">
                            @php
                                $userName =
                                    $comment['user_id'] === 'System'
                                        ? 'System'
                                        : \App\Models\User::find($comment['user_id'])->name ?? 'Unknown';
                            @endphp
                            <b>{{ $userName }}</b>
                        </p>
                    </div>
                    <div class="flex">
                        <p class="text-sm text-gray-600">
                            @php
                                $formattedTime = Carbon::parse($comment['time'])->diffForHumans();
                            @endphp
                            {{ $formattedTime }}
                        </p>
                    </div>
                </div>
                <div class="flex">
                    <p class="text-gray-800 mt-1">{{ $comment['comment'] }}</p>
                </div>
                <div class="flex flex-col space-y-1">
                    @if (isset($comment['images']) && is_array($comment['images']) && count($comment['images']) > 0)
                        <div class="flex mt-6 text-sm font-semibold">
                            <p>Attachment : </p>
                        </div>
                        <div class="flex gap-1">
                            @foreach ($comment['images'] as $imageUrl)
                                <div class="flex items-center">
                                    <x-filament::button outlined size="xs" color="gray"
                                        href="{{ $imageUrl }}" tag="a" target="_blank"
                                        icon="phosphor-paperclip-duotone">
                                        {{ basename($imageUrl) }}
                                    </x-filament::button>
                                    <p>&nbsp</p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    @else
        <p class="text-gray-500">No progress update yet.</p>
    @endif
</div>
