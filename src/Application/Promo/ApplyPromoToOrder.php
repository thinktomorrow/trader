<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo;

use Thinktomorrow\Trader\Application\Promo\LinePromo\LineDiscount;
use Thinktomorrow\Trader\Application\Promo\OrderPromo\OrderDiscount;
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
        static::validateDiscounts($discounts);

        $hasBeenApplied = false;

        // Check if order is in customer hands still. Or can admin add promo afterwards?
        if (!$order->getOrderState()->inCustomerHands()) return;

        // Loop over different discountables: lines, shipping, payment, order
        foreach ($discounts as $discount) {

            if ($discount instanceof LineDiscount) {
                foreach ($order->getLines() as $line) {
                    if ($discount->isApplicable($order, $line)) {
                        $discount->apply($order, $line, $this->orderRepository->nextDiscountReference());
                        $hasBeenApplied = true;
                    }
                }
            }

            if ($discount instanceof OrderDiscount) {
                foreach ($order->getShippings() as $shipping) {
                    if ($discount->isApplicable($order, $shipping)) {
                        $discount->apply($order, $shipping, $this->orderRepository->nextDiscountReference());
                        $hasBeenApplied = true;
                    }
                }

                // Global order discount
                if ($discount->isApplicable($order, $order)) {
                    $discount->apply($order, $order, $this->orderRepository->nextDiscountReference());
                    $hasBeenApplied = true;
                }
            }


        }

        if ($coupon_code && $hasBeenApplied) {
            $order->setEnteredCouponCode($coupon_code);
        }
    }

    private static function validateDiscounts($discounts): void
    {
        foreach ($discounts as $discount) {
            if (!$discount instanceof OrderDiscount && !$discount instanceof LineDiscount) {
                throw new \InvalidArgumentException('Invalid discount type [' . $discount::class . '] provided in child entities for OrderPromo.');
            }
        }
    }
}
