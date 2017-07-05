<?php

namespace Thinktomorrow\Trader\Tests\Unit\Stubs;

use Thinktomorrow\Trader\Discounts\Domain\Discount;
use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscount;
use Thinktomorrow\Trader\Discounts\Domain\DiscountDescription;
use Thinktomorrow\Trader\Discounts\Domain\DiscountId;
use Thinktomorrow\Trader\Discounts\Domain\Types\BaseDiscount;
use Thinktomorrow\Trader\Discounts\Domain\Types\TypeKey;
use Thinktomorrow\Trader\Order\Domain\ItemCollection;
use Thinktomorrow\Trader\Order\Domain\Order;

class ConcretePercentageOffDiscount extends BaseDiscount implements Discount
{
    private $type;

    public function __construct(DiscountId $id, array $conditions, array $adjusters)
    {
        $this->validateParameters($conditions, $adjusters);

        $this->id = $id;
        $this->conditions = $conditions;
        $this->type = TypeKey::fromDiscount($this);
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
            TypeKey::fromString('stub'),
            new DiscountDescription('foobar', []),
            $affectedItems
        );
    }
}