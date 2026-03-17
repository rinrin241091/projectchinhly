<?php

namespace App\Providers;

use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Illuminate\Support\Facades\URL;
use Filament\Support\Facades\FilamentAsset;
use App\Models\Activity;

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

        Activity::creating(function (Activity $activity): void {
            if (app()->runningInConsole() || ! request()) {
                return;
            }

            $userAgent = (string) request()->userAgent();
            $existingProperties = $activity->properties;
            $properties = [];

            if (is_array($existingProperties)) {
                $properties = $existingProperties;
            } elseif (is_object($existingProperties) && method_exists($existingProperties, 'toArray')) {
                $properties = $existingProperties->toArray();
            }

            $properties['ip_address'] = request()->ip();
            $properties['mac_address'] = (string) (request()->header('X-Client-Mac')
                ?? request()->header('X-MAC-Address')
                ?? request()->header('X-Mac-Address')
                ?? 'N/A');
            $properties['os'] = self::detectOs($userAgent);
            $properties['device_type'] = self::detectDeviceType($userAgent);
            $properties['browser'] = self::detectBrowser($userAgent);

            $activity->properties = $properties;
        });


    }

    private static function detectOs(string $userAgent): string
    {
        $ua = strtolower($userAgent);

        return match (true) {
            str_contains($ua, 'windows') => 'Windows',
            str_contains($ua, 'android') => 'Android',
            str_contains($ua, 'iphone'), str_contains($ua, 'ipad'), str_contains($ua, 'ios') => 'iOS',
            str_contains($ua, 'mac os'), str_contains($ua, 'macintosh') => 'macOS',
            str_contains($ua, 'linux') => 'Linux',
            default => 'Unknown',
        };
    }

    private static function detectDeviceType(string $userAgent): string
    {
        $ua = strtolower($userAgent);

        return match (true) {
            str_contains($ua, 'tablet'), str_contains($ua, 'ipad') => 'Tablet',
            str_contains($ua, 'mobile'), str_contains($ua, 'iphone'), str_contains($ua, 'android') => 'Mobile',
            $userAgent === '' => 'Unknown',
            default => 'Desktop',
        };
    }

    private static function detectBrowser(string $userAgent): string
    {
        $ua = strtolower($userAgent);

        return match (true) {
            str_contains($ua, 'edg/') => 'Edge',
            str_contains($ua, 'opr/'), str_contains($ua, 'opera') => 'Opera',
            str_contains($ua, 'firefox/') => 'Firefox',
            str_contains($ua, 'chrome/') => 'Chrome',
            str_contains($ua, 'safari/') && ! str_contains($ua, 'chrome/') => 'Safari',
            default => 'Unknown',
        };
    }
}
