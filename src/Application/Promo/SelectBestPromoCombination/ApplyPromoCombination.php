<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\SelectBestPromoCombination;

use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters\AdjustPayment;
use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters\AdjustShipping;
use Thinktomorrow\Trader\Application\Promo\ApplicableDiscounts\ApplicableDiscount;
use Thinktomorrow\Trader\Application\Promo\ApplicableDiscounts\OrderSubtotalDiscount;
use Thinktomorrow\Trader\Application\Promo\ApplicableDiscounts\PaymentDiscount;
use Thinktomorrow\Trader\Application\Promo\ApplicableDiscounts\PercentageOffOrderSubtotalDiscount;
use Thinktomorrow\Trader\Application\Promo\ApplicableDiscounts\ShippingDiscount;
use Thinktomorrow\Trader\Domain\Model\Order\AppliedDiscount\AppliedDiscountId;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\OrderRepository;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\DiscountableType;

/** Uses the same application order for trial copies and the real order. */
final class ApplyPromoCombination
{
    public function __construct(
        private OrderRepository $orderRepository,
        private AdjustShipping $adjustShipping,
        private AdjustPayment $adjustPayment,
    ) {}

    public function clearDiscounts(Order $order): void
    {
        foreach ([$order, ...$order->getLines(), ...$order->getShippings(), ...$order->getPayments()] as $discountable) {
            $discountable->deleteDiscounts();
        }
    }

    public function apply(Order $order, PromoCombination $combination, PromoCandidates $prepared, bool $simulation = false, ?SelectedServiceProfiles $profiles = null): void
    {
        $sequence = 0;

        foreach ($order->getLines() as $line) {
            foreach ($combination->productDiscounts[$line->getDiscountableId()->get()] ?? [] as $discount) {
                $discount->apply($order, $line, $this->nextAppliedDiscountReference($simulation, $sequence));
            }
        }

        // Recalculate shipping and payment after product prices change.
        if ($profiles === null) {
            $this->adjustShipping->adjust($order);
            $this->adjustPayment->adjust($order);
        } else {
            $this->adjustShipping->adjustWithProfile($order, $profiles->shipping);
            $this->adjustPayment->adjustWithMethod($order, $profiles->payment);
        }

        foreach ([DiscountableType::order, DiscountableType::shipping, DiscountableType::payment] as $type) {
            $discounts = [];
            foreach ($combination->promos as $promo) {
                foreach ($prepared->discountsFor($promo) as $discount) {
                    if (($type === DiscountableType::order && $discount instanceof OrderSubtotalDiscount)
                        || ($type === DiscountableType::shipping && $discount instanceof ShippingDiscount)
                        || ($type === DiscountableType::payment && $discount instanceof PaymentDiscount)) {
                        $discounts[] = $discount;
                    }
                }
            }

            // Apply percentage discounts before fixed amounts on the subtotal.
            if ($type === DiscountableType::order) {
                usort($discounts, fn (ApplicableDiscount $a, ApplicableDiscount $b): int => ($a instanceof PercentageOffOrderSubtotalDiscount ? 0 : 1) <=> ($b instanceof PercentageOffOrderSubtotalDiscount ? 0 : 1));
            }

            $targets = match ($type) {
                DiscountableType::order => [$order],
                DiscountableType::shipping => $order->getShippings(),
                DiscountableType::payment => $order->getPayments(),
                default => [],
            };

            foreach ($targets as $target) {
                $applicable = array_values(array_filter($discounts, fn (ApplicableDiscount $discount): bool => $discount->isApplicable($order, $target)));

                foreach ($applicable as $discount) {
                    $discount->apply($order, $target, $this->nextAppliedDiscountReference($simulation, $sequence));
                }
            }
        }
    }

    private function nextAppliedDiscountReference(bool $simulation, int &$sequence): AppliedDiscountId
    {
        if ($simulation) {
            return AppliedDiscountId::fromString('simulated-discount-'.++$sequence);
        }

        return $this->orderRepository->nextAppliedDiscountReference();
    }
}
