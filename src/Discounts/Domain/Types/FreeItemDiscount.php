<?php

namespace Thinktomorrow\Trader\Discounts\Domain\Types;

use Assert\Assertion;
use Thinktomorrow\Trader\Common\Domain\Description;
use Thinktomorrow\Trader\Common\Domain\Price\Cash;
use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscount;
use Thinktomorrow\Trader\Discounts\Domain\Discount;
use Thinktomorrow\Trader\Discounts\Domain\DiscountId;
use Thinktomorrow\Trader\Discounts\Domain\Exceptions\CannotApplyDiscount;
use Thinktomorrow\Trader\Discounts\Domain\OrderDiscount;
use Thinktomorrow\Trader\Orders\Domain\Item;
use Thinktomorrow\Trader\Orders\Domain\Order;

final class FreeItemDiscount extends BaseDiscount implements Discount, OrderDiscount
{
    /**
     * @var Item[]
     */
    private $free_items;

    public function __construct(DiscountId $id, array $conditions, array $adjusters)
    {
        parent::__construct($id, $conditions, $adjusters);

        $this->free_items = $adjusters['free_items'];
        //$this->type = TypeKey::fromDiscount($this);
    }

    /**
     * Adds a free product to the cart based on given conditions.
     *
     * @param Order $order
     *
     * @throws CannotApplyDiscount
     */
    public function apply(Order $order)
    {
        // Check conditions first
        if (!$this->applicable($order)) {
            throw new CannotApplyDiscount();
        }

        // Since the products are offered as free, make sure each item has a 0,00 price
        foreach ($this->free_items as $item) {
            $item->addToDiscountTotal($item->subtotal()); // TODO: maybe create a method e.g. makeFree()?
            $order->items()->add($item);
        }

        $order->addDiscount(new AppliedDiscount(
            $this->id,
            $this->type,
            $this->createDescription(),
            Cash::make(0)
        ));
    }

    private function createDescription()
    {
        return new Description(
            $this->type,
            ['free_items' => $this->free_items]
        );
    }

    /**
     * @param array $conditions
     * @param array $adjusters
     */
    protected function validateParameters(array $conditions, array $adjusters)
    {
        parent::validateParameters($conditions, $adjusters);

        if (!isset($adjusters['free_items'])) {
            throw new \InvalidArgumentException('Missing adjuster value \'free_items\', required for discount '.get_class($this));
        }

        if (!is_array($adjusters['free_items'])) {
            throw new \InvalidArgumentException('Invalid adjuster value \'free_items\' for discount '.get_class($this).'. Array is expected.');
        }

        Assertion::allIsInstanceOf($adjusters['free_items'], Item::class);
    }
}
