<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\LinePromo;

use Thinktomorrow\Trader\Domain\Common\Map\Mappable;
use Thinktomorrow\Trader\Domain\Common\Price\DiscountPrice;
use Thinktomorrow\Trader\Domain\Common\Price\ItemDiscountPrice;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountableItem;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountId;
use Thinktomorrow\Trader\Domain\Model\Order\Order;

interface LineDiscount extends Mappable
{
    public static function fromMappedData(array $state, array $aggregateState, array $conditions): static;

    public function isApplicable(Order $order, DiscountableItem $discountable): bool;

    public function apply(Order $order, DiscountableItem $discountable, DiscountId $nextDiscountId): void;

    public function getDiscountPrice(Order $order, DiscountableItem $discountable): ItemDiscountPrice;

    /**
     * The total discount on the order. This is not used in the price calculation, but rather
     * for sorting the available order promos to determine which one has the highest
     * discount impact. This way the visitor receives the best available discount.
     *
     * @param Order $order
     * @return DiscountPrice
     */
    public function getCombinedDiscountPrice(Order $order): DiscountPrice;
}
