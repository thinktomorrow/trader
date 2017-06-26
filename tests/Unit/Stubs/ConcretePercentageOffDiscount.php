<?php

namespace Thinktomorrow\Trader\Tests\Unit\Stubs;

use Thinktomorrow\Trader\Discounts\Domain\Discount;
use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscount;
use Thinktomorrow\Trader\Discounts\Domain\DiscountDescription;
use Thinktomorrow\Trader\Discounts\Domain\DiscountId;
use Thinktomorrow\Trader\Discounts\Domain\DiscountType;
use Thinktomorrow\Trader\Order\Domain\ItemCollection;
use Thinktomorrow\Trader\Order\Domain\Order;

class ConcretePercentageOffDiscount implements Discount
{
    /**
     * @var
     */
    private $id;

    public function __construct($id = null)
    {
        $id = $id ?: rand(1,9999);
        $this->id = DiscountId::fromInteger($id);
    }

    public function id(): DiscountId
    {
        return $this->id;
    }

    public function apply(Order $order): AppliedDiscount
    {
        $affectedItems = new ItemCollection();

        return new AppliedDiscount(
            $this->id,
            DiscountType::fromString('stub'),
            new DiscountDescription('foobar', []),
            $affectedItems
        );
    }
}