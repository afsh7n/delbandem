<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;

class UpdateExpiredSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:update-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update expired subscriptions status';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking for expired subscriptions...');

        $expiredCount = Subscription::where('status', Subscription::STATUS_ACTIVE)
            ->where('end_date', '<=', now())
            ->update(['status' => Subscription::STATUS_EXPIRED]);

        $this->info("Updated {$expiredCount} expired subscription(s).");

        return self::SUCCESS;
    }
}

