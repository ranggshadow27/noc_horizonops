<?php

namespace App\Providers;

use App\Models\TmoData;
use App\Models\TmoDeviceChange;
use App\Observers\TMODataObserver;
use App\Models\TmoImage;
use App\Models\TmoTask;
use App\Observers\TMODeviceChangeObserver;
use App\Observers\TMOImageObserver;
use App\Observers\TmoTaskObserver;
use Illuminate\Support\ServiceProvider;

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
    }
}
