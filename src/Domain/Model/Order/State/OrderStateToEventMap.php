<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\State;

use Thinktomorrow\Trader\Domain\Common\Map\HasSimpleMapping;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\CartAbandoned;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\CartCompleted;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\CartQueuedForDeletion;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\CartRevived;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\OrderCancelled;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\OrderCancelledByMerchant;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\OrderConfirmed;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\OrderDelivered;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\OrderPacked;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\OrderPaid;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\OrderPartiallyDelivered;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\OrderPartiallyPacked;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\OrderPartiallyPaid;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\OrderQuoted;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\QuotedOrderConfirmed;

class OrderStateToEventMap
{
    use HasSimpleMapping;

    public static function getDefaultMapping(): array
    {
        return [
            OrderState::cart_abandoned->value => CartAbandoned::class,
            OrderState::cart_revived->value => CartRevived::class,
            OrderState::cart_queued_for_deletion->value => CartQueuedForDeletion::class,
            OrderState::cart_complete->value => CartCompleted::class,
            OrderState::confirmed->value => OrderConfirmed::class,
            OrderState::cancelled->value => OrderCancelled::class,
            OrderState::cancelled_by_merchant->value => OrderCancelledByMerchant::class,
            OrderState::quoted->value => OrderQuoted::class,
            OrderState::quote_confirmed->value => QuotedOrderConfirmed::class,
            OrderState::paid->value => OrderPaid::class,
            OrderState::partially_paid->value => OrderPartiallyPaid::class,
            OrderState::packed->value => OrderPacked::class,
            OrderState::partially_packed->value => OrderPartiallyPacked::class,
            OrderState::delivered->value => OrderDelivered::class,
            OrderState::partially_delivered->value => OrderPartiallyDelivered::class,
        ];
    }
}
