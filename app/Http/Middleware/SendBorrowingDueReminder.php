<?php

namespace App\Http\Middleware;

use App\Models\Borrowing;
use Carbon\Carbon;
use Closure;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SendBorrowingDueReminder
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->routeIs('filament.*')) {
            return $next($request);
        }

        if (! auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();
        if ($user->role === 'admin') {
            return $next($request);
        }

        // Avoid duplicate reminder spam while navigating; show at most once per day.
        $today = now()->toDateString();
        if (session('borrowing_due_reminder_date') === $today) {
            return $next($request);
        }

        $openBorrowings = Borrowing::query()
            ->where('user_id', $user->id)
            ->where('approval_status', 'approved')
            ->whereNull('returned_at')
            ->get(['id', 'borrowed_at', 'due_at']);

        $overdueCount = 0;
        $upcomingCount = 0;
        $now = Carbon::today();

        foreach ($openBorrowings as $borrowing) {
            $dueDate = $borrowing->due_at
                ? Carbon::parse($borrowing->due_at)->startOfDay()
                : Carbon::parse($borrowing->borrowed_at)->addDays(30)->startOfDay();

            if ($dueDate->lt($now)) {
                $overdueCount++;
                continue;
            }

            $daysLeft = $now->diffInDays($dueDate, false);
            if ($daysLeft >= 0 && $daysLeft <= 3) {
                $upcomingCount++;
            }
        }

        if ($overdueCount > 0) {
            Notification::make()
                ->title('Bạn có hồ sơ đã quá hạn trả')
                ->body("Hiện có {$overdueCount} hồ sơ đã quá hạn. Vui lòng trả hồ sơ sớm.")
                ->danger()
                ->persistent()
                ->send();
        }

        if ($upcomingCount > 0) {
            Notification::make()
                ->title('Nhắc hạn trả hồ sơ')
                ->body("Có {$upcomingCount} hồ sơ sẽ đến hạn trong 3 ngày tới. Vui lòng chủ động trả đúng hạn.")
                ->warning()
                ->send();
        }

        if (($overdueCount + $upcomingCount) > 0) {
            session(['borrowing_due_reminder_date' => $today]);
        }

        return $next($request);
    }
}
