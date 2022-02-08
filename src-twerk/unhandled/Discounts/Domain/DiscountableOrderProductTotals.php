<?php declare(strict_types=1);

namespace Thinktomorrow\Trader\Discounts\Domain;

use Money\Money;
use Thinktomorrow\Trader\Order\Domain\Order;
use Thinktomorrow\Trader\Order\Domain\OrderProduct;

class DiscountableOrderProductTotals
{
    private array $data;
    private array $alreadyIncluded = [];
    private array $alreadyExcluded = [];

    public function __construct()
    {
        $this->data = ['quantity' => 0, 'quantified_total' => Money::EUR(0)];
    }

    public function get(Order $order, array $conditions = []): array
    {
        if (empty($conditions)) {
            foreach ($order->getItems() as $item) {
                $this->add($item);
            }

            return $this->data;
        }

        foreach ($conditions as $condition) {
            $this->adjustByCondition($order, $condition);
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
     * @param Order $order
     * @param Condition $condition
     */
    public function adjustByCondition(Order $order, Condition $condition): void
    {
        foreach ($order->getItems() as $item) {
            if ($condition->check($order, $item)) {
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
    }

    private function add(OrderProduct $item)
    {
        $this->data['quantity'] += $item->getQuantity();
        $this->data['quantified_total'] = $this->data['quantified_total']->add($item->getTotal());
    }

    private function remove(OrderProduct $item)
    {
        $this->data['quantity'] = $this->data['quantity'] - $item->getQuantity();
        $this->data['quantified_total'] = $this->data['quantified_total']->subtract($item->getTotal());
    }

    private function alreadyIncluded(OrderProduct $item)
    {
        return in_array($item->id(), $this->alreadyIncluded);
    }

    private function alreadyExcluded(OrderProduct $item)
    {
        return in_array($item->getId(), $this->alreadyExcluded);
    }

    private function markAsIncluded(OrderProduct $item)
    {
        $this->alreadyIncluded[] = $item->getId();
    }

    private function markAsExcluded(OrderProduct $item)
    {
        $this->alreadyExcluded[] = $item->getId();
    }
}
