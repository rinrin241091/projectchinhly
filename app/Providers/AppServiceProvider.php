<?php

namespace App\Providers;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Illuminate\Support\Facades\URL;
use Filament\Support\Facades\FilamentAsset;

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
        
        FilamentAsset::register([
            Css::make('custom-styles', __DIR__ . '/../../resources/css/custom-filament.css'),
            Js::make('custom-scripts',__DIR__ . '/../../resources/js/custom-print.js')
               
        ]);

        
    }
}
