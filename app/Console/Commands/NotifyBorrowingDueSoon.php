<?php

namespace App\Console\Commands;

use App\Models\Borrowing;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;

class NotifyBorrowingDueSoon extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'borrowings:notify-due-soon';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify borrowers and managers 3 days before borrowing due date';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $targetDate = now()->addDays(3)->toDateString();

        $dueSoonBorrowings = Borrowing::query()
            ->with(['archiveRecord.organization', 'user'])
            ->where('approval_status', 'approved')
            ->whereNull('returned_at')
            ->whereDate('due_at', '=', $targetDate)
            ->whereNull('due_soon_notified_at')
            ->get();

        if ($dueSoonBorrowings->isEmpty()) {
            $this->info('No borrowings due in 3 days.');
            return self::SUCCESS;
        }

        foreach ($dueSoonBorrowings as $borrowing) {
            $recordTitle = $borrowing->archiveRecord?->title ?? ('#' . $borrowing->archive_record_id);
            $dueDateText = optional($borrowing->due_at)?->format('d/m/Y') ?? $targetDate;

            if ($borrowing->user) {
                Notification::make()
                    ->title('Tài liệu sắp đến hạn trả')
                    ->body("Tài liệu \"{$recordTitle}\" sắp đến hạn trả vào {$dueDateText}.")
                    ->warning()
                    ->sendToDatabase($borrowing->user, isEventDispatched: true);
            }

            $organizationId = $borrowing->archiveRecord?->organization_id;
            if (! $organizationId) {
                continue;
            }

            $managers = User::query()
                ->where(function ($query) {
                    $query->where('role', 'admin')->orWhere('role', 'teamlead');
                })
                ->whereHas('organizations', fn ($query) => $query->where('organizations.id', $organizationId))
                ->get();

            if ($managers->isEmpty()) {
                continue;
            }

            Notification::make()
                ->title('Tài liệu sắp đến hạn trả')
                ->body("Tài liệu \"{$recordTitle}\" sắp đến hạn trả vào {$dueDateText}.")
                ->warning()
                ->sendToDatabase($managers, isEventDispatched: true);

            $borrowing->update([
                'due_soon_notified_at' => now(),
            ]);
        }

        $this->info('Due-soon notifications sent successfully.');

        return self::SUCCESS;
    }
}
