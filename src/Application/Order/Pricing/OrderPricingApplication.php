<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Order\Pricing;

use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters\AdjustOrderPricingSnapshot;
use Thinktomorrow\Trader\Application\Order\Pricing\Exceptions\CannotRecalculateFrozenOrderWithoutPricingSnapshot;
use Thinktomorrow\Trader\Application\Order\Pricing\Exceptions\FrozenOrderPricingWouldChange;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\OrderRepository;
use Thinktomorrow\Trader\Domain\Model\Order\Snapshot\OrderPricingSnapshot;

final class OrderPricingApplication
{
    public function __construct(
        private OrderRepository $orderRepository,
        private AdjustOrderPricingSnapshot $adjustOrderPricingSnapshot,
    ) {}

    public function recalculate(OrderId $orderId, bool $persist = true): PricingRecalculationResult
    {
        $order = $this->orderRepository->find($orderId);
        $previous = $order->findPricingSnapshot();
        $pricingIsFrozen = $order->hasFrozenPricing();

        if ($pricingIsFrozen && $previous === null) {
            throw new CannotRecalculateFrozenOrderWithoutPricingSnapshot(
                'Cannot safely recalculate frozen order '.$orderId->get().' without an existing pricing snapshot.',
            );
        }

        $recalculated = $this->adjustOrderPricingSnapshot->calculate($order);

        if ($pricingIsFrozen) {
            $this->assertFrozenPricingAmountsRemainUnchanged($orderId, $previous, $recalculated);
        }

        if ($persist) {
            $order->applyPricingSnapshot($recalculated);
            $this->orderRepository->save($order);
        }

        return new PricingRecalculationResult($previous, $recalculated, $persist);
    }

    private function assertFrozenPricingAmountsRemainUnchanged(
        OrderId $orderId,
        OrderPricingSnapshot $previous,
        OrderPricingSnapshot $recalculated,
    ): void {
        $amounts = [
            'subtotal_excl' => [$previous->getSubtotalExcl(), $recalculated->getSubtotalExcl()],
            'subtotal_incl' => [$previous->getSubtotalIncl(), $recalculated->getSubtotalIncl()],
            'shipping_excl' => [$previous->getShippingExcl(), $recalculated->getShippingExcl()],
            'shipping_incl' => [$previous->getShippingIncl(), $recalculated->getShippingIncl()],
            'payment_excl' => [$previous->getPaymentExcl(), $recalculated->getPaymentExcl()],
            'payment_incl' => [$previous->getPaymentIncl(), $recalculated->getPaymentIncl()],
            'discount_excl' => [$previous->getDiscountExcl(), $recalculated->getDiscountExcl()],
            'discount_incl' => [$previous->getDiscountIncl(), $recalculated->getDiscountIncl()],
            'total_excl' => [$previous->getTotalExcl(), $recalculated->getTotalExcl()],
            'total_incl' => [$previous->getTotalIncl(), $recalculated->getTotalIncl()],
        ];

        foreach ($amounts as $field => [$frozenAmount, $recalculatedAmount]) {
            if (! $frozenAmount->equals($recalculatedAmount)) {
                throw new FrozenOrderPricingWouldChange(sprintf(
                    'Recalculated %s [%s] differs from frozen amount [%s] for order %s.',
                    $field,
                    $recalculatedAmount->getAmount(),
                    $frozenAmount->getAmount(),
                    $orderId->get(),
                ));
            }
        }
    }
}
