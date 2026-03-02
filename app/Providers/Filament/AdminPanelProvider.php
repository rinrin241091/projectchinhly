<?php

namespace App\Providers\Filament;

use App\Filament\Pages\BulkCreateShelves;
use App\Filament\Pages\SelectOrganization;
use App\Filament\Pages\BulkCreateBoxs;
use App\Filament\Pages\ChangePassword;
use Filament\Support\Facades\FilamentAsset;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use App\Http\Middleware\EnsureOrganizationSelected;
use Filament\Navigation\MenuItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Navigation\NavigationGroup;
use Illuminate\Support\Facades\URL;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('dashboard')
            ->path('dashboard')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->darkmode(condition:false)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                \App\Filament\Pages\Dashboard::class,
                BulkCreateBoxs::class,
                SelectOrganization::class,
            ])
            ->font(family:'popins')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
            ])
            ->userMenuItems([
                MenuItem::make()
                    ->label('Đổi mật khẩu')
                    ->url(fn (): string => ChangePassword::getUrl())
                    ->icon('heroicon-o-key'),
            ])
            ->renderHook(
                'panels::topbar.start',
                fn (): string => view('components.filament.archival-display')->render(),
            )
            ->middleware([
                

                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                EnsureOrganizationSelected::class,
                //  'ensure.organization',
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
