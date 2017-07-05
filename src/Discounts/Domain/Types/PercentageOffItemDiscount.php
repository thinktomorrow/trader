<?php

namespace Thinktomorrow\Trader\Discounts\Domain\Types;

use Thinktomorrow\Trader\Discounts\Domain\Discount;
use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscount;
use Thinktomorrow\Trader\Discounts\Domain\DiscountConditions;
use Thinktomorrow\Trader\Discounts\Domain\DiscountDescription;
use Thinktomorrow\Trader\Discounts\Domain\DiscountId;
use Thinktomorrow\Trader\Discounts\Domain\ItemDiscount;
use Thinktomorrow\Trader\Order\Domain\ItemCollection;
use Thinktomorrow\Trader\Order\Domain\Order;
use Thinktomorrow\Trader\Common\Price\Percentage;

final class PercentageOffItemDiscount extends BaseItemDiscount implements Discount, ItemDiscount
{
    /**
     * @var \Thinktomorrow\Trader\Common\Price\Percentage
     */
    private $percentage;

    /**
     * @var TypeKey
     */
    private $type;

    public function __construct(DiscountId $id, array $conditions, array $adjusters)
    {
        $this->validateParameters($conditions, $adjusters);

        $this->id = $id;
        $this->conditions = $conditions;
        $this->percentage = $adjusters['percentage'];
        $this->adjusters = $adjusters;
        $this->type = TypeKey::fromDiscount($this);
    }

    public function id(): DiscountId
    {
        return $this->id;
    }

    public function apply(Order $order)
    {
        // Loop over each item to check if it applies for this discount. If so apply
        // TODO If it could possible apply but one of the conditions isn't yet met, we can keep it
        // as 'AlmostApplicableDiscounts'. This allows us to push incentives to the visitor

        foreach($order->items() as $item)
        {
            // Check conditions first
            if( ! $this->applicable($order, $item->id()))
            {
                continue;
            }

            $discountAmount = $item->salePrice()->multiply($this->getAffectedItemQuantity($item))
                                   ->multiply($this->percentage->asFloat());

            // Protect against negative overflow where order total would dive under zero
            if($discountAmount->greaterThan($item->subtotal())) $discountAmount = $item->subtotal();

            // TODO: how to get reasons of nonapplicable? like: you need extra 40,- to benefit from this promo.
            // Like when you enter couponcode and you want the reason for non-acceptance
            $item->addToDiscountTotal($discountAmount);

            // TODO: just add appliedDiscount Here no?

            $item->addDiscount(new AppliedDiscount(
                $this->id,
                $this->type,
                $this->createDiscountDescription(),
                $discountAmount
            ));
        }
    }

    private function createDiscountDescription()
    {
        return new DiscountDescription(
            $this->type,
            [
                'percent' => $this->percentage->asPercent()
            ]
        );
    }

    /**
     * @param array $conditions
     * @param array $adjusters
     */
    protected function validateParameters(array $conditions, array $adjusters)
    {
        parent::validateParameters($conditions, $adjusters);

        if (!isset($adjusters['percentage'])) {
            throw new \InvalidArgumentException('Missing adjuster value \'percentage\', required for discount '.get_class($this));
        }

        if (!$adjusters['percentage'] instanceof Percentage) {
            throw new \InvalidArgumentException('Invalid adjuster value \'percentage\' for discount '.get_class($this).'. Instance of '.Percentage::class.' is expected.');
        }
    }
}