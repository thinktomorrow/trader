<?php

namespace App\Discounts;

use Thinktomorrow\Trader\Discounts\Domain\Discount;
use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscount;
use Thinktomorrow\Trader\Discounts\Domain\DiscountConditions;
use Thinktomorrow\Trader\Discounts\Domain\DiscountDescription;
use Thinktomorrow\Trader\Discounts\Domain\DiscountId;
use Thinktomorrow\Trader\Discounts\Domain\DiscountType;
use Thinktomorrow\Trader\Order\Domain\ItemCollection;
use Thinktomorrow\Trader\Order\Domain\Order;
use Thinktomorrow\Trader\Price\Percentage;

class PercentageOffItemDiscount implements Discount
{
    const TYPE = 'percentage_off_item';

    /**
     * @var DiscountId
     */
    private $id;

    /**
     * @var Percentage
     */
    private $percentage;
    /**
     * @var DiscountConditions
     */
    private $conditions;

    public function __construct(DiscountId $id, Percentage $percentage, DiscountConditions $conditions = null)
    {
        $this->id = $id;
        $this->percentage = $percentage;
        $this->conditions = $conditions ?: new DiscountConditions([]);

        // Force conditions to apply for item
        $this->conditions = $this->conditions->add('applies_to', 'item');
    }

    public function id(): DiscountId
    {
        return $this->id;
    }

    public function apply(Order $order): AppliedDiscount
    {
        // Loop over each item to check if it applies for this discount. If so apply
        // TODO If it could possible apply but one of the conditions isn't yet met, we can keep it
        // as 'AlmostApplicableDiscounts'. This allows us to push incentives to the visitor

        $affectedItems = new ItemCollection();

        foreach($order->items() as $item)
        {
            if($this->conditions->applicableToItem($order, $item->id()))
            {
                // TODO: how to get reasons of nonapplicable? like: you need extra 40,- to benefit from this promo.
                // Like when you enter couponcode and you want the reason for non-acceptance
                $item->addToDiscountTotal(
                    $item->salePrice()->multiply($this->conditions->getAffectedItemQuantity())
                                      ->multiply($this->percentage->asFloat())
                );

                $affectedItems->add($item);
            }
        }

        return new AppliedDiscount(
            $this->id,
            DiscountType::fromString(self::TYPE),
            $this->createDiscountDescription(),
            $affectedItems
        );
    }

    private function createDiscountDescription()
    {
        return new DiscountDescription(
            self::TYPE,
            [
                'percent' => $this->percentage->asPercent()
            ]
        );
    }
}