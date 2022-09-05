<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\OrderPromo\Discounts;

use Assert\Assertion;
use Thinktomorrow\Trader\Application\Promo\OrderPromo\OrderCondition;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discount;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discountable;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountId;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountTotal;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Promo\DiscountId as PromoDiscountId;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoId;

abstract class BaseDiscount
{
    protected readonly PromoId $promoId;
    protected readonly PromoDiscountId $promoDiscountId;
    protected array $promoData;

    /** @var OrderCondition[] */
    protected array $conditions;

    protected function isApplicable(Order $order, Discountable $discountable): bool
    {
        foreach ($this->conditions as $condition) {
            if (! $condition->check($order, $discountable)) {
                return false;
            }
        }

        return true;
    }

    public function apply(Order $order, Discountable $discountable, DiscountId $nextDiscountId): void
    {
        if (! $this->isApplicable($order, $discountable)) {
            return;
        }

        $discount = Discount::create(
            $order->orderId,
            $nextDiscountId,
            $discountable->getDiscountableType(),
            $discountable->getDiscountableId(),
            $this->promoId,
            $this->promoDiscountId,
            $this->getDiscountTotal($order, $discountable),
            $this->promoData,
        );

        $discountable->addDiscount($discount);
    }

    public static function fromMappedData(array $state, array $aggregateState, array $conditions): static
    {
        Assertion::allIsInstanceOf($conditions, OrderCondition::class);

        $discount = new static();
        $discount->promoId = PromoId::fromString($aggregateState['promo_id']);
        $discount->promoDiscountId = PromoDiscountId::fromString($state['discount_id']);
        $discount->promoData = json_decode($aggregateState['data'], true);
        $discount->conditions = $conditions;

        return $discount;
    }

    public function getCombinedDiscountTotal(Order $order): DiscountTotal
    {
        return $this->getDiscountTotal($order, $order);
    }

    abstract public function getDiscountTotal(Order $order, Discountable $discountable): DiscountTotal;
}
