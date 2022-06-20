<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\ApplicablePromo;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Map\Mappable;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discountable;
use Thinktomorrow\Trader\Domain\Model\Order\Order;

interface Discount extends Mappable
{
    public static function fromMappedData(array $state, array $aggregateState, array $conditions): static;

    public function isApplicable(Order $order, Discountable $discountable): bool;

    public function apply(Order $order, Discountable $discountable): void;

    public function getDiscountTotal(Order $order, Discountable $discountable): Money;
}
