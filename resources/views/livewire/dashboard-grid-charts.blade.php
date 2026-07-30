<div>
    <!-- CSS Gridstack -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/gridstack@9.2.0/dist/gridstack.min.css" />

    <!-- Gridstack Container khusus Charts -->
    <div class="grid-stack" id="charts-gridstack">
        <!-- Widget 1: SP Performance Trend -->
        <div class="grid-stack-item" gs-x="0" gs-y="0" gs-w="12" gs-h="4" gs-id="widget_perf"
            wire:ignore>
            <div class="grid-stack-item-content p-2 bg-white dark:bg-gray-900 rounded-xl shadow">
                @livewire(\App\Filament\Widgets\SPPerformanceTrendChart::class)
            </div>
        </div>

        <!-- Widget 2: SP Rank Trend -->
        <div class="grid-stack-item" gs-x="0" gs-y="4" gs-w="12" gs-h="4" gs-id="widget_rank"
            wire:ignore>
            <div class="grid-stack-item-content p-2 bg-white dark:bg-gray-900 rounded-xl shadow">
                @livewire(\App\Filament\Widgets\SPRankTrendChart::class)
            </div>
        </div>
    </div>

    <!-- JS Gridstack -->
    <script src="https://cdn.jsdelivr.net/npm/gridstack@9.2.0/dist/gridstack-all.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let grid = GridStack.init({
                float: true,
                column: 12,
                resizable: {
                    handles: 'e, se, s, sw, w'
                },
            }, '#charts-gridstack');

            // Restore layout
            let savedLayout = localStorage.getItem('user_charts_layout');
            if (savedLayout) {
                try {
                    let items = JSON.parse(savedLayout);
                    items.forEach(item => {
                        let el = document.querySelector(`[gs-id="${item.id}"]`);
                        if (el) {
                            grid.update(el, {
                                x: item.x,
                                y: item.y,
                                w: item.w,
                                h: item.h
                            });
                        }
                    });
                } catch (e) {}
            }

            // Save layout
            grid.on('change', function(event, items) {
                let layout = grid.save(false);
                localStorage.setItem('user_charts_layout', JSON.stringify(layout));
            });
        });
    </script>
</div>
