<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use App\Filament\Resources\DocumentResource;

class FilamentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Filament::serving(function () {
            // Đăng ký các resource và navigation
            $this->registerNavigation();
            Filament::registerResources([
                DocumentResource::class,
            ]);
        });
    }

    protected function registerNavigation(): void
    {
        // Kiểm tra role của user hiện tại
        $user = Auth::user();
        
        if ($user && $user->role === 'admin') {
            // Admin thấy tất cả menu
            return;
        }
        
        // User chỉ thấy các menu không phải quản lý user
        Filament::registerNavigationGroups([
            'Quản lý hồ sơ',
            'Quản lý kho',
            'Báo cáo',
        ]);
    }
}
