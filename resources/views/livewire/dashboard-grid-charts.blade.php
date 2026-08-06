<div x-data="{
    fullscreenWidget: null,
    toggleFullscreen(widgetId) {
        if (this.fullscreenWidget === widgetId) {
            this.fullscreenWidget = null;
        } else {
            this.fullscreenWidget = widgetId;
        }

        // Memicu pemicuan ulang (resize) dengan delay kecil agar class CSS selesai berganti lebih dahulu
        $nextTick(() => {
            setTimeout(() => {
                window.dispatchEvent(new Event('resize'));
            }, 150);
        });
    }
}"
    @keydown.escape.window="
    if (fullscreenWidget) {
        fullscreenWidget = null;
        $nextTick(() => {
            setTimeout(() => { window.dispatchEvent(new Event('resize')); }, 150);
        });
    }
">

    <div class="space-y-6 w-full overflow-hidden">

        <!-- Widget 1: SP Performance Trend -->
        <div
            :class="fullscreenWidget === 'perf'
                ?
                'fixed inset-0 z-50 p-6 bg-white dark:bg-gray-900 overflow-y-auto flex flex-col justify-between' :
                'relative bg-white dark:bg-gray-900 p-4 rounded-xl shadow  w-full overflow-hidden'">
            <!-- Header Action -->
            <div class="flex justify-between items-center mb-2">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Performance Chart</span>
                <button type="button" @click="toggleFullscreen('perf')"
                    class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-600 hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-400 px-2 py-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                    <template x-if="fullscreenWidget !== 'perf'">
                        <div class="flex items-center gap-1">
                            <x-heroicon-m-arrows-pointing-out class="w-4 h-4" />
                            <span>Fullscreen</span>
                        </div>
                    </template>
                    <template x-if="fullscreenWidget === 'perf'">
                        <div class="flex items-center gap-1 text-danger-600">
                            <x-heroicon-m-x-mark class="w-5 h-5" />
                            <span>Exit Fullscreen (ESC)</span>
                        </div>
                    </template>
                </button>
            </div>

            <!-- Content Livewire Chart -->
            <div class="flex-1 w-full min-w-0 overflow-hidden touch-pan-y" wire:ignore>
                @livewire(\App\Filament\Widgets\SPPerformanceTrendChart::class)
            </div>
        </div>

        <!-- Widget 2: SP Rank Trend -->
        <div
            :class="fullscreenWidget === 'rank'
                ?
                'fixed inset-0 z-50 p-6 bg-white dark:bg-gray-900 overflow-y-auto flex flex-col justify-between' :
                'relative bg-white dark:bg-gray-900 p-4 rounded-xl shadow w-full overflow-hidden'">
            <!-- Header Action -->
            <div class="flex justify-between items-center mb-2">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Rank Chart</span>
                <button type="button" @click="toggleFullscreen('rank')"
                    class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-600 hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-400 px-2 py-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                    <template x-if="fullscreenWidget !== 'rank'">
                        <div class="flex items-center gap-1">
                            <x-heroicon-m-arrows-pointing-out class="w-4 h-4" />
                            <span>Fullscreen</span>
                        </div>
                    </template>
                    <template x-if="fullscreenWidget === 'rank'">
                        <div class="flex items-center gap-1 text-danger-600">
                            <x-heroicon-m-x-mark class="w-5 h-5" />
                            <span>Exit Fullscreen (ESC)</span>
                        </div>
                    </template>
                </button>
            </div>

            <!-- Content Livewire Chart -->
            <div class="flex-1 w-full min-w-0 overflow-hidden" wire:ignore>
                @livewire(\App\Filament\Widgets\SPRankTrendChart::class)
            </div>
        </div>

    </div>
</div>
