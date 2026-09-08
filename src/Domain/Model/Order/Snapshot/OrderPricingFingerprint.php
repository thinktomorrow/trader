<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Snapshot;

use Thinktomorrow\Trader\Domain\Common\Price\ItemDiscountPrice;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\Shipping;

final class OrderPricingFingerprint
{
    public static function calculate(Order $order): string
    {
        $inputs = [];

        foreach ($order->getLines() as $line) {
            $unitPrice = $line->getUnitPrice();
            $discounts = [];

            foreach ($line->getDiscounts() as $discount) {
                $discountPrice = $discount->getDiscountPrice();
                $discounts[] = [
                    'id' => $discount->discountId->get(),
                    'amount' => $discountPrice->getAuthoritativeAmount()->getAmount(),
                    'tax_mode' => $discountPrice->getTaxMode()->value,
                    'vat_rate' => $discountPrice instanceof ItemDiscountPrice ? $discountPrice->getVatPercentage()->get() : null,
                ];
            }

            usort($discounts, fn (array $left, array $right): int => $left['id'] <=> $right['id']);
            $inputs[] = [
                'type' => 'line',
                'id' => $line->lineId->get(),
                'unit_amount' => $unitPrice->getAuthoritativeAmount()->getAmount(),
                'tax_mode' => $unitPrice->getTaxMode()->value,
                'quantity' => $line->getQuantity()->asInt(),
                'vat_rate' => $unitPrice->getVatPercentage()->get(),
                'discounts' => $discounts,
            ];
        }

        foreach ([
            'shipping' => $order->getShippings(),
            'payment' => $order->getPayments(),
        ] as $serviceType => $services) {
            foreach ($services as $service) {
                $isShipping = $service instanceof Shipping;
                $price = $isShipping ? $service->getShippingCost() : $service->getPaymentCost();
                $serviceId = $isShipping ? $service->shippingId->get() : $service->paymentId->get();
                $serviceDiscounts = [];

                foreach ($service->getDiscounts() as $discount) {
                    $discountPrice = $discount->getDiscountPrice();
                    $serviceDiscounts[] = [
                        'id' => $discount->discountId->get(),
                        'amount' => $discountPrice->getAuthoritativeAmount()->getAmount(),
                        'tax_mode' => $discountPrice->getTaxMode()->value,
                    ];
                }

                usort($serviceDiscounts, fn (array $left, array $right): int => $left['id'] <=> $right['id']);
                $inputs[] = [
                    'type' => $serviceType,
                    'id' => $serviceId,
                    'amount' => $price->getAuthoritativeAmount()->getAmount(),
                    'tax_mode' => $price->getTaxMode()->value,
                    'discounts' => $serviceDiscounts,
                ];
            }
        }

        foreach ($order->getDiscounts() as $discount) {
            $price = $discount->getDiscountPrice();
            $inputs[] = [
                'type' => 'order_discount',
                'id' => $discount->discountId->get(),
                'amount' => $price->getAuthoritativeAmount()->getAmount(),
                'tax_mode' => $price->getTaxMode()->value,
            ];
        }

        usort($inputs, fn (array $left, array $right): int => [$left['type'], $left['id']] <=> [$right['type'], $right['id']]);

        return 'pricing-v4:'.hash('sha256', json_encode([
            'currency' => 'EUR',
            'vat_exempt' => $order->isVatExempt(),
            'components' => $inputs,
        ], JSON_THROW_ON_ERROR));
    }
}
