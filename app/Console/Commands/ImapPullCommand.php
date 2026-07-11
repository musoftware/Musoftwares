<?php

namespace App\Console\Commands;

use App\Services\GuestTicketInbox;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ImapPullCommand extends Command
{
    protected $signature = 'imap:pull {--dry-run : Parse messages without persisting or marking seen}';

    protected $description = 'Pull unseen IMAP messages into Guest Tickets and mark them as seen';

    public function handle(GuestTicketInbox $inbox): int
    {
        $key = (string) config('imap.lock_key', 'imap-pull');
        $ttl = (int) config('imap.lock_seconds', 110);
        $lock = Cache::lock("{$key}-lock", $ttl);
        if (! $lock->get()) {
            $this->warn('IMAP pull already in progress, skipping.');

            return self::SUCCESS;
        }

        try {
            $dry = (bool) $this->option('dry-run');
            $stats = $inbox->pull($dry);

            if ($dry) {
                $this->info('Dry-run: parsed ' . $stats['fetched'] . ' message(s).');

                return self::SUCCESS;
            }

            $this->info(sprintf(
                'IMAP pull complete: fetched=%d matched=%d created_tickets=%d errors=%d',
                $stats['fetched'],
                $stats['matched'],
                $stats['created_tickets'],
                $stats['errors']
            ));

            return $stats['errors'] > 0 ? self::FAILURE : self::SUCCESS;
        } finally {
            optional($lock)->release();
        }
    }
}
