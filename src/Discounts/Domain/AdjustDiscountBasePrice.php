<?php

declare(strict_types = 1);

namespace Thinktomorrow\Trader\Discounts\Domain;

use Money\Money;
use Thinktomorrow\Trader\Common\Contracts\HasParameters;
use Thinktomorrow\Trader\Orders\Domain\Item;
use Thinktomorrow\Trader\Orders\Domain\Order;

class AdjustDiscountBasePrice
{
    private $order;
    private $discountBasePrice;

    private $alreadyIncluded = [];
    private $alreadyExcluded = [];

    public function addConditions(array $conditions)
    {
        foreach($conditions as $condition)
        {
            if( ! $condition instanceof HasParameters) continue;

            $this->addCondition($condition);
        }

        return $this;
    }

    /**
     * CHECK PASS -> already added      -> Ignore
     *            -> not added yet      -> already subtracted -> ignore
     *                                  -> not subtracted yet -> add
     * CHECK FAIL -> already subtracted -> ignore
     *            -> not subtracted yet -> already added -> subtract
     *                                  -> not added yet -> ignore
     */
    public function addCondition(HasParameters $condition)
    {
        if(!$this->order) throw new \DomainException('Order value is required. Add it via setOrder method.');

        foreach($this->order->items() as $item)
        {
            if ($condition->check($this->order, $item)) {

                if($this->alreadyIncluded($item) || $this->alreadyExcluded($item)) continue;

                $this->discountBasePrice = $this->discountBasePrice->add($item->total());

                $this->markAsIncluded($item);
            }
            else{
                if($this->alreadyExcluded($item)) continue;

                if($this->alreadyIncluded($item))
                {
                    // Subtraction is only possible if the item was already added in the first place
                    $this->discountBasePrice = $this->discountBasePrice->subtract($item->total());
                }

                $this->markAsExcluded($item);
            }
        }

        return $this;
    }

    private function alreadyIncluded(Item $item)
    {
        return in_array($item->purchasableId()->get(), $this->alreadyIncluded);
    }

    private function alreadyExcluded(Item $item)
    {
        return in_array($item->purchasableId()->get(), $this->alreadyExcluded);
    }

    private function markAsIncluded(Item $item)
    {
        $this->alreadyIncluded[] = $item->purchasableId()->get();
    }

    private function markAsExcluded(Item $item)
    {
        $this->alreadyExcluded[] = $item->purchasableId()->get();
    }

    public function discountBasePrice(): Money
    {
        return $this->discountBasePrice;
    }

    public function setDiscountBasePrice(Money $discountBasePrice)
    {
        $this->discountBasePrice = $discountBasePrice;

        return $this;
    }

    public function setOrder(Order $order)
    {
        $this->order = $order;

        return $this;
    }
}