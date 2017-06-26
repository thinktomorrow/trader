<?php

namespace App\Discounts;

use Thinktomorrow\Trader\Discounts\Domain\Discount;
use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscount;
use Thinktomorrow\Trader\Discounts\Domain\DiscountDescription;
use Thinktomorrow\Trader\Discounts\Domain\DiscountId;
use Thinktomorrow\Trader\Discounts\Domain\DiscountType;
use Thinktomorrow\Trader\Order\Domain\ItemCollection;
use Thinktomorrow\Trader\Order\Domain\Order;
use Thinktomorrow\Trader\Price\Percentage;

class PercentageOffDiscount implements Discount
{
    const TYPE = 'percentage_off';

    /**
     * @var DiscountId
     */
    private $id;

    /**
     * @var Percentage
     */
    private $percentage;

    public function __construct(DiscountId $id, Percentage $percentage)
    {
        $this->id = $id;
        $this->percentage = $percentage;
    }

    public function id(): DiscountId
    {
        return $this->id;
    }

    public function apply(Order $order): AppliedDiscount
    {
        $order->addToDiscountTotal(
            $order->subtotal()->multiply($this->percentage->asFloat())
        );

        return new AppliedDiscount(
            $this->id,
            DiscountType::fromString(self::TYPE),
            $this->createDiscountDescription(),
            new ItemCollection() // no affected items. Discount is applied on order level
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