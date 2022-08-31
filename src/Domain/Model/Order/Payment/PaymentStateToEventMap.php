<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Payment;

use Thinktomorrow\Trader\Domain\Common\Map\HasSimpleMapping;
use Thinktomorrow\Trader\Domain\Model\Order\Events\PaymentStates\PaymentPaid;
use Thinktomorrow\Trader\Domain\Model\Order\Events\PaymentStates\PaymentFailed;
use Thinktomorrow\Trader\Domain\Model\Order\Events\PaymentStates\PaymentRefunded;
use Thinktomorrow\Trader\Domain\Model\Order\Events\PaymentStates\PaymentInitialized;
use Thinktomorrow\Trader\Domain\Model\Order\Events\PaymentStates\PaymentMarkedPaidByMerchant;

class PaymentStateToEventMap
{
    use HasSimpleMapping;

    public static function getDefaultMapping(): array
    {
        return [
            PaymentState::initialized->value => PaymentInitialized::class,
            PaymentState::paid->value => PaymentPaid::class,
            PaymentState::paid_by_merchant->value => PaymentMarkedPaidByMerchant::class,
            PaymentState::canceled->value => PaymentFailed::class,
            PaymentState::failed->value => PaymentFailed::class,
            PaymentState::expired->value => PaymentFailed::class,
            PaymentState::refunded->value => PaymentRefunded::class,
            PaymentState::charged_back->value => PaymentRefunded::class,
        ];
    }
}
