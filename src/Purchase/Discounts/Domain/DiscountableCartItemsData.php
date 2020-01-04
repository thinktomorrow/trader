<?php

declare(strict_types=1);

namespace Optiphar\Discounts;

use Money\Money;
use Thinktomorrow\Trader\Purchase\Cart\Cart;
use Thinktomorrow\Trader\Purchase\Cart\CartItem;

class DiscountableCartItemsData
{
    private $cart;

    /** @var array */
    private $data;

    /** @var array */
    private $alreadyIncluded = [];

    /** @var array */
    private $alreadyExcluded = [];

    public function __construct()
    {
        $this->data = ['quantity' => 0, 'quantified_total' => Money::EUR(0)];
    }

    public function get(Cart $cart, array $conditions = []): array
    {
        if(empty($conditions)){
            foreach ($cart->items() as $item) {
                $this->add($item);
            }

            return $this->data;
        }

        foreach ($conditions as $condition) {
            $this->adjustByCondition($cart, $condition);
        }

        return $this->data;
    }

    /**
     * CHECK PASS -> already added      -> Ignore
     *            -> not added yet      -> already subtracted -> ignore
     *                                  -> not subtracted yet -> add
     * CHECK FAIL -> already subtracted -> ignore
     *            -> not subtracted yet -> already added -> subtract
     *                                  -> not added yet -> ignore.
     *
     * @param Condition $condition
     * @return DiscountableCartItemsData
     */
    public function adjustByCondition(Cart $cart, Condition $condition)
    {
        foreach ($cart->items() as $item) {
            if ($condition->check($cart, $item)) {
                if ($this->alreadyIncluded($item) || $this->alreadyExcluded($item)) {
                    continue;
                }

                $this->add($item);

                $this->markAsIncluded($item);
            } else {
                if ($this->alreadyExcluded($item)) {
                    continue;
                }

                if ($this->alreadyIncluded($item)) {
                    // Subtraction is only possible if the item was already added in the first place
                    $this->remove($item);
                }

                $this->markAsExcluded($item);
            }
        }

        return $this;
    }

    private function add(CartItem $item)
    {
        $this->data['quantity'] = $this->data['quantity'] + $item->quantity();
        $this->data['quantified_total'] = $this->data['quantified_total']->add($item->quantifiedTotal());
    }

    private function remove(CartItem $item)
    {
        $this->data['quantity'] = $this->data['quantity'] - $item->quantity();
        $this->data['quantified_total'] = $this->data['quantified_total']->subtract($item->quantifiedTotal());
    }

    private function alreadyIncluded(CartItem $item)
    {
        return in_array($item->id(), $this->alreadyIncluded);
    }

    private function alreadyExcluded(CartItem $item)
    {
        return in_array($item->id(), $this->alreadyExcluded);
    }

    private function markAsIncluded(CartItem $item)
    {
        $this->alreadyIncluded[] = $item->id();
    }

    private function markAsExcluded(CartItem $item)
    {
        $this->alreadyExcluded[] = $item->id();
    }
}
