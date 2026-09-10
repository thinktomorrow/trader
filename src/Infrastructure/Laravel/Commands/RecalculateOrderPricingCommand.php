<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Thinktomorrow\Trader\Application\Order\Pricing\OrderPricingApplication;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;

final class RecalculateOrderPricingCommand extends Command
{
    protected $signature = 'trader:recalculate-order-pricing
                            {orderId : Order UUID}
                            {--dry-run : Calculate and validate without persisting}';

    protected $description = 'Recalculate and validate the atomic pricing snapshot for one order.';

    public function handle(OrderPricingApplication $application): int
    {
        $orderId = OrderId::fromString((string) $this->argument('orderId'));
        $dryRun = (bool) $this->option('dry-run');
        $result = $dryRun
            ? $application->recalculate($orderId, false)
            : DB::transaction(function () use ($application, $orderId) {
                DB::table('trader_orders')
                    ->where('order_id', $orderId->get())
                    ->lockForUpdate()
                    ->first();

                return $application->recalculate($orderId);
            });

        $this->table(
            ['Value', 'Previous', 'Recalculated'],
            [
                ['Fingerprint', $result->previous?->getPricingFingerprint() ?? '-', $result->recalculated->getPricingFingerprint() ?? '-'],
                ['Total excl.', $result->previous?->getTotalExcl()->getAmount() ?? '-', $result->recalculated->getTotalExcl()->getAmount()],
                ['Total VAT', $result->previous?->getTotalVat()->getAmount() ?? '-', $result->recalculated->getTotalVat()->getAmount()],
                ['Total incl.', $result->previous?->getTotalIncl()->getAmount() ?? '-', $result->recalculated->getTotalIncl()->getAmount()],
            ],
        );

        $this->info($dryRun ? 'Dry run completed; no changes were persisted.' : 'Pricing snapshot recalculated and persisted.');

        return self::SUCCESS;
    }
}
