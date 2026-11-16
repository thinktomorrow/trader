<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\ApplicableConditions;

use Thinktomorrow\Trader\Domain\Common\Map\Mappable;
use Thinktomorrow\Trader\Domain\Model\Order\AppliedDiscount\DiscountableItem;
use Thinktomorrow\Trader\Domain\Model\Order\Order;

interface ApplicableCondition extends Mappable
{
    public static function fromMappedData(array $state, array $aggregateState): static;

    public function check(Order $order, DiscountableItem $discountable): bool;
}
