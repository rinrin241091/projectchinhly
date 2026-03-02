<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationSelected
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // ⚙️ Bỏ qua các route không thuộc Filament
        if (! $request->routeIs('filament.*')) {
            return $next($request);
        }

        // ⚙️ Bỏ qua các route đặc biệt không cần session
        if ($request->routeIs([
            'filament.dashboard.pages.select-organization',
            'filament.dashboard.auth.*',
            'filament.dashboard.login',
            'filament.dashboard.logout',
        ])) {
            return $next($request);
        }

        // ⚙️ Nếu chưa chọn phông → chuyển hướng
        if (! session()->has('organization_id')) {
            return redirect()->route('filament.dashboard.pages.select-organization');
        }

        return $next($request);
    }
}
