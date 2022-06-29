<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\OrderPromo;

use Thinktomorrow\Trader\Domain\Common\Map\Mappable;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discountable;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountId;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountTotal;
use Thinktomorrow\Trader\Domain\Model\Order\Order;

interface OrderDiscount extends Mappable
{
    public static function fromMappedData(array $state, array $aggregateState, array $conditions): static;

    public function isApplicable(Order $order, Discountable $discountable): bool;

    public function apply(Order $order, Discountable $discountable, DiscountId $nextDiscountId): void;

    public function getDiscountTotal(Order $order, Discountable $discountable): DiscountTotal;

    /**
     * The total discount on the order. This is not used in the price calculation, but rather
     * for sorting the available order promos to determine which one has the highest
     * discount impact. This way the visitor receives the best available discount.
     *
     * @param Order $order
     * @return DiscountTotal
     */
    public function getCombinedDiscountTotal(Order $order): DiscountTotal;
}
