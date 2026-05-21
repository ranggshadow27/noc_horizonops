<div class="space-y-6">
    {{-- <h3 class="text-lg font-semibold">Live Broadcast Monitor</h3> --}}

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-3 text-left">Session Name</th>
                    <th class="px-4 py-3 text-left">Area</th>
                    <th class="px-4 py-3 text-left">Interval</th>
                    <th class="px-4 py-3 text-left">Progress</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left">Last Processed</th>
                    <th class="px-4 py-3 text-left">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($sessions ?? [] as $session)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium">{{ $session->name }}</td>
                        <td class="px-4 py-3">{{ $session->area }}</td>
                        <td class="px-4 py-3">{{ $session->interval_minutes }} menit</td>

                        <td class="px-4 py-3">
                            @php
                                $progress =
                                    $session->total_logs > 0
                                        ? round(($session->sent_count / $session->total_logs) * 100)
                                        : 0;
                            @endphp

                            <p class="text-gray-600">{{ $session->sent_count }} / {{ $session->total_logs }}</p>
                        </td>

                        <td class="px-4 py-3">
                            @php
                                $badgeColor = match ($session->status) {
                                    'active' => 'success',
                                    'completed' => 'success',
                                    'paused' => 'warning',
                                    'stopped' => 'danger',
                                    default => 'gray',
                                };
                            @endphp
                            <x-filament::badge :color="$badgeColor">
                                {{ ucfirst($session->status) }}
                            </x-filament::badge>
                        </td>

                        <td class="px-4 py-3 text-gray-600">
                            {{ $session->last_processed_at?->diffForHumans() ?? '-' }}
                        </td>

                        <td class="px-4 py-3">
                            <div class="flex gap-2">
                                {{-- Pause / Resume --}}
                                @if ($session->status === 'active')
                                    <x-filament::button icon="phosphor-pause-duotone" outlined size="sm"
                                        color="warning" wire:click="pauseSession({{ $session->id }})">
                                        Pause
                                    </x-filament::button>
                                @elseif($session->status === 'paused')
                                    <x-filament::button icon="phosphor-play-duotone" outlined size="sm"
                                        color="success" wire:click="resumeSession({{ $session->id }})">
                                        Resume
                                    </x-filament::button>
                                @endif

                                {{-- Stop Button --}}
                                @if ($session->status !== 'completed' && $session->status !== 'stopped' && ($session->is_expired ?? false))
                                    <x-filament::button icon="phosphor-stop-circle-duotone" outlined size="sm"
                                        color="danger" wire:click="stopSession({{ $session->id }})">
                                        Stop
                                    </x-filament::button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center text-gray-500 italic">
                            There are no active broadcast sessions yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
