<?php

namespace App\Console\Commands;

use App\Models\Borrowing;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;

class NotifyOverdueBorrowings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'borrowings:notify-overdue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify managers about overdue out-of-authority borrowing requests';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $overdueBorrowings = Borrowing::query()
            ->with(['archiveRecord.organization', 'user'])
            ->where('approval_status', 'approved')
            ->whereNull('returned_at')
            ->whereDate('due_at', '<', now()->toDateString())
            ->whereNull('overdue_notified_at')
            ->get();

        if ($overdueBorrowings->isEmpty()) {
            $this->info('No overdue borrowings found.');
            return self::SUCCESS;
        }

        $notifiedManagers = 0;

        foreach ($overdueBorrowings as $borrowing) {
            $organizationId = $borrowing->archiveRecord?->organization_id;

            $recordTitle = $borrowing->archiveRecord?->title ?? ('#' . $borrowing->archive_record_id);

            if ($borrowing->user) {
                Notification::make()
                    ->title('Tài liệu đã quá hạn trả')
                    ->body('Tài liệu "' . $recordTitle . '" đã quá hạn trả.')
                    ->danger()
                    ->sendToDatabase($borrowing->user, isEventDispatched: true);
            }

            if (! $organizationId) {
                $borrowing->update([
                    'overdue_notified_at' => now(),
                ]);

                continue;
            }

            $managers = User::query()
                ->where(function ($query) {
                    $query
                        ->where('role', 'admin')
                        ->orWhere('role', 'teamlead');
                })
                ->whereHas('organizations', fn ($query) => $query->where('organizations.id', $organizationId))
                ->get();

            if ($managers->isEmpty()) {
                continue;
            }

            Notification::make()
                ->title('Tài liệu đã quá hạn trả')
                ->body('Tài liệu "' . $recordTitle . '" đã quá hạn trả.')
                ->danger()
                ->sendToDatabase($managers, isEventDispatched: true);

            $notifiedManagers += $managers->count();

            $borrowing->update([
                'overdue_notified_at' => now(),
            ]);
        }

        $this->info('Overdue notifications sent to ' . $notifiedManagers . ' manager recipients.');

        return self::SUCCESS;
    }
}
