<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\ApplicablePromo;

use Assert\Assertion;
use Thinktomorrow\Trader\Domain\Model\Order\Order;

class ApplicablePromo
{
    /** @var Discount[] */
    private array $discounts;

//    public function isApplicable(Order $order, Discountable $discountable): bool
//    {
//        // Loop conditions
//        foreach($this->conditions as $condition) {
//            if(!$condition->check($order, $discountable)) {
//                return false;
//            }
//        }
//
//        return true;
//    }

    public function apply(Order $order): void
    {
        // TODO: check if order is in customer hands still? Or can admin add promo afterwards??

        // Loop over different discountables...


//        if(!$this->isApplicable($order, $discountable)) return;

        foreach ($this->discounts as $discount) {
            foreach ($order->getShippings() as $shipping) {
                if ($discount->isApplicable($order, $shipping)) {
                    $discount->apply($order, $shipping);
                }
            }

            foreach ($order->getLines() as $line) {
                if ($discount->isApplicable($order, $line)) {
                    $discount->apply($order, $line);
                }
            }

            if ($discount->isApplicable($order, $order)) {
                $discount->apply($order, $order);
            }
        }
    }

    public static function fromMappedData(array $state, array $childEntities = []): static
    {
        Assertion::allIsInstanceOf($childEntities[Discount::class], Discount::class);

        $promo = new static();

        $promo->discounts = $childEntities[Discount::class];

        return $promo;
    }
}
