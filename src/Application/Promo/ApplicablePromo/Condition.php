<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\ApplicablePromo;

use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Common\Map\Mappable;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discountable;

interface Condition extends Mappable
{
    public static function fromMappedData(array $state, array $aggregateState): static;

    public function check(Order $order, Discountable $discountable): bool;
}
