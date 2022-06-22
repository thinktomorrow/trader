<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\OrderPromo;

use Thinktomorrow\Trader\Domain\Common\Map\Mappable;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discountable;
use Thinktomorrow\Trader\Domain\Model\Order\Order;

interface OrderCondition extends Mappable
{
    public static function fromMappedData(array $state, array $aggregateState): static;

    public function check(Order $order, Discountable $discountable): bool;
}
