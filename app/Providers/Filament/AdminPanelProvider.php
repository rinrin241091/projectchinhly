<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard as DashboardPage;
use App\Filament\Pages\BulkCreateShelves;
use App\Filament\Pages\QuickArchiveSearch;
use App\Filament\Pages\ProgressReport;
use App\Filament\Pages\RecordDocumentReport;
use App\Filament\Pages\RecordStatisticsReport;
use App\Filament\Pages\ReportSummary;
use App\Filament\Pages\RoomDirectoryReport;
use App\Filament\Pages\SelectOrganization;
use App\Filament\Pages\BulkCreateBoxs;
use App\Filament\Pages\ChangePassword;
use App\Filament\Pages\Auth\Login as CaptchaLogin;
use App\Filament\Resources\ActivityResource;
use App\Filament\Resources\ArchivalResource;
use App\Filament\Resources\ArchiveRecordItemResource;
use App\Filament\Resources\ArchiveRecordResource;
use App\Filament\Resources\BorrowingResource;
use App\Filament\Resources\BoxResource;
use App\Filament\Resources\DocTypeResource;
use App\Filament\Resources\DocumentResource;
use App\Filament\Resources\OrganizationResource;
use App\Filament\Resources\ProjectResource;
use App\Filament\Resources\RecordTypeResource;
use App\Filament\Resources\ShelveResource;
use App\Filament\Resources\StorageResource;
use App\Filament\Resources\UserResource;
use Filament\Support\Facades\FilamentAsset;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use App\Http\Middleware\EnsureOrganizationSelected;
use App\Http\Middleware\SendBorrowingDueReminder;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationBuilder;
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
            ->login(CaptchaLogin::class)
            // Navigation is always enabled; visibility will be controlled via CSS hook
            ->colors([
                'primary' => Color::Amber,
            ])
            ->databaseNotifications()
            ->databaseNotificationsPolling('5s')
            ->darkmode(condition:false)
            ->sidebarWidth('15.5rem')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->navigation(function (NavigationBuilder $builder): NavigationBuilder {
                $resourceItems = static fn (string $resourceClass): array => $resourceClass::canViewAny()
                    ? $resourceClass::getNavigationItems()
                    : [];

                $pageItems = static fn (string $pageClass): array => $pageClass::shouldRegisterNavigation()
                    ? $pageClass::getNavigationItems()
                    : [];

                return $builder->groups([
                    NavigationGroup::make()->items([
                        ...$pageItems(DashboardPage::class),
                        ...$resourceItems(ActivityResource::class),
                        ...$resourceItems(ProjectResource::class),
                        ...$pageItems(QuickArchiveSearch::class),
                    ]),
                    NavigationGroup::make('Nhập liệu - Biên mục')->items([
                        ...$resourceItems(ArchiveRecordResource::class),
                        ...$resourceItems(ArchiveRecordItemResource::class),
                        ...$resourceItems(DocumentResource::class),
                    ]),
                    NavigationGroup::make('Khai thác - Thống kê')->items([
                        ...$resourceItems(ArchivalResource::class),
                        ...$resourceItems(OrganizationResource::class),
                        ...$resourceItems(StorageResource::class),
                        ...$resourceItems(ShelveResource::class),
                        ...$resourceItems(BoxResource::class),
                        ...$resourceItems(RecordTypeResource::class),
                        ...$resourceItems(DocTypeResource::class),
                    ]),
                    NavigationGroup::make()->items([
                        ...$resourceItems(BorrowingResource::class),
                    ]),
                    NavigationGroup::make('Quản lý hệ thống')->items([
                        ...$resourceItems(UserResource::class),
                    ]),
                    NavigationGroup::make('Báo cáo - Thống kê')->items([
                        ...$pageItems(ReportSummary::class),
                        ...$pageItems(ProgressReport::class),
                        ...$pageItems(RecordStatisticsReport::class),
                        ...$pageItems(RecordDocumentReport::class),
                        ...$pageItems(RoomDirectoryReport::class),
                    ]),
                ]);
            })
            ->pages([
                \App\Filament\Pages\Dashboard::class,
                BulkCreateBoxs::class,
                SelectOrganization::class,
            ])
            ->font(family:'Poppins')
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
            ->renderHook(
                'panels::sidebar.start',
                fn (): string => (
                    ! session()->has('selected_archival_id') ||
                    request()->routeIs('filament.dashboard.pages.select-organization')
                )
                    ? '<style>.fi-sidebar-nav, .fi-sidebar-ctn { display: none !important; }</style>'
                    : '',
            )
            ->renderHook(
                'panels::body.end',
                fn (): string => auth()->user()?->role === 'admin'
                    ? '<script>
                        (() => {
                            const endpoint = "' . route('borrowings.pending-count') . '";
                            let lastPendingCount = null;

                            const checkPendingCount = async () => {
                                try {
                                    const response = await fetch(endpoint, {
                                        method: "GET",
                                        credentials: "same-origin",
                                        headers: {
                                            "X-Requested-With": "XMLHttpRequest",
                                        },
                                    });

                                    if (!response.ok) {
                                        return;
                                    }

                                    const data = await response.json();
                                    const currentPendingCount = Number(data.count ?? 0);

                                    if (lastPendingCount === null) {
                                        lastPendingCount = currentPendingCount;
                                        return;
                                    }

                                    if (currentPendingCount !== lastPendingCount) {
                                        window.location.reload();
                                    }
                                } catch (error) {
                                    // no-op
                                }
                            };

                            checkPendingCount();
                            setInterval(checkPendingCount, 5000);
                        })();
                    </script>'
                    : '',
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
                SendBorrowingDueReminder::class,
                //  'ensure.organization',
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
