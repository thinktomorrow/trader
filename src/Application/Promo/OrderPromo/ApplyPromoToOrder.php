<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\OrderPromo;

use Assert\Assertion;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\OrderRepository;

class ApplyPromoToOrder
{
    private OrderRepository $orderRepository;

    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param Order $order
     * @param OrderDiscount[] $discounts
     * @param string|null $coupon_code
     * @return void
     */
    public function apply(Order $order, array $discounts, ?string $coupon_code = null): void
    {
        Assertion::allIsInstanceOf($discounts, OrderDiscount::class);

        $hasBeenApplied = false;

        // TODO: check if order is in customer hands still? Or can admin add promo afterwards??

        // Loop over different discountables: lines, shipping, payment, order
        foreach ($discounts as $discount) {
            foreach ($order->getShippings() as $shipping) {
                if ($discount->isApplicable($order, $shipping)) {
                    $discount->apply($order, $shipping, $this->orderRepository->nextDiscountReference());
                    $hasBeenApplied = true;
                }
            }

            // TODO: add payment as discountable as well.

            foreach ($order->getLines() as $line) {
                if ($discount->isApplicable($order, $line)) {
                    $discount->apply($order, $line, $this->orderRepository->nextDiscountReference());
                    $hasBeenApplied = true;
                }
            }

            if ($discount->isApplicable($order, $order)) {
                $discount->apply($order, $order, $this->orderRepository->nextDiscountReference());
                $hasBeenApplied = true;
            }
        }

        if ($coupon_code && $hasBeenApplied) {
            $order->setEnteredCouponCode($coupon_code);
        }
    }
}
