<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo;

use Thinktomorrow\Trader\Application\Promo\LinePromo\LineDiscount;
use Thinktomorrow\Trader\Application\Promo\OrderPromo\OrderDiscount;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\OrderRepository;
use Thinktomorrow\Trader\TraderConfig;

class ApplyPromoToOrder
{
    public function __construct(private OrderRepository $orderRepository, private TraderConfig $config) {}

    /**
     * @param  OrderDiscount[]  $discounts
     */
    public function apply(Order $order, array $discounts, ?string $coupon_code = null, PromoApplicationScope $scope = PromoApplicationScope::All): void
    {
        static::validateDiscounts($discounts);

        $hasBeenApplied = false;

        // Check if order is in customer hands still. Or can admin add promo afterwards?
        if (! $order->getOrderState()->inCustomerHands()) {
            return;
        }

        // Loop over different discountables: lines, shipping, payment, order
        foreach ($discounts as $discount) {

            if ($discount instanceof LineDiscount && $scope !== PromoApplicationScope::ServicesAndOrder) {
                foreach ($order->getLines() as $line) {
                    if ($discount->isApplicable($order, $line)) {

                        $discount->setCalculationTaxMode($this->config->getItemDiscountTaxMode());

                        $discount->apply($order, $line, $this->orderRepository->nextDiscountReference());
                        $hasBeenApplied = true;
                    }
                }
            }

            if ($discount instanceof OrderDiscount && $scope !== PromoApplicationScope::Lines) {
                foreach ($order->getShippings() as $shipping) {
                    if ($discount->isApplicable($order, $shipping)) {
                        $discount->apply($order, $shipping, $this->orderRepository->nextDiscountReference());
                        $hasBeenApplied = true;
                    }
                }

                foreach ($order->getPayments() as $payment) {
                    if ($discount->isApplicable($order, $payment)) {
                        $discount->apply($order, $payment, $this->orderRepository->nextDiscountReference());
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

    protected static function validateDiscounts($discounts): void
    {
        foreach ($discounts as $discount) {
            if (! $discount instanceof OrderDiscount && ! $discount instanceof LineDiscount) {
                throw new \InvalidArgumentException('Invalid discount type ['.$discount::class.'] provided in child entities for OrderPromo.');
            }
        }
    }
}
