<?php

namespace App\Providers;

use App\Models\NmtTickets;
use App\Models\TmoData;
use App\Models\TmoDeviceChange;
use App\Models\TmoImage;
use App\Models\TmoTask;
use App\Observers\NmtTicketObserver;
use App\Observers\TMODataObserver;
use App\Observers\TMODeviceChangeObserver;
use App\Observers\TMOImageObserver;
use App\Observers\TmoTaskObserver;
use Illuminate\Support\ServiceProvider;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        TmoDeviceChange::observe(TMODeviceChangeObserver::class);
        TmoData::observe(TMODataObserver::class);
        TmoImage::observe(TMOImageObserver::class);
        TmoTask::observe(TmoTaskObserver::class);
        NmtTickets::observe(NmtTicketObserver::class);

        FilamentColor::register([
            'danger' => Color::Red,
            'gray' => Color::Zinc,
            'info' => Color::Blue,
            'primary' => Color::Amber,
            'success' => Color::Green,
            'warning' => Color::Amber,
        ]);

        Filament::serving(function () {
            Filament::registerNavigationGroups([
                NavigationGroup::make()
                    ->label('Operational')
                    ->icon('phosphor-cpu-duotone'),
                NavigationGroup::make()
                    ->label('Site Management')
                    ->icon('phosphor-stack-duotone'),
                NavigationGroup::make()
                    ->label('Trouble Tickets')
                    ->icon('phosphor-ticket-duotone'),
                NavigationGroup::make()
                    ->label('TMO')
                    ->icon('phosphor-hand-withdraw-duotone'),
            ]);
        });
    }
}
