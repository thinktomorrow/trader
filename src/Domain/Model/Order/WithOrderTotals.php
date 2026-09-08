<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Price\ItemDiscountPrice;
use Thinktomorrow\Trader\Domain\Common\Vat\VatAllocatedLine;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;
use Thinktomorrow\Trader\Domain\Model\Order\Exceptions\VatSnapshotNotCalculated;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\Shipping;

trait WithOrderTotals
{
    protected ?OrderVatSnapshot $vatSnapshot = null;

    public function applyVatSnapshot(OrderVatSnapshot $snapshot): void
    {
        $snapshot->assertMatchesTotalExcl($this->getTotalExcl());
        $snapshot->assertMatchesPricingFingerprint($this->getPricingFingerprint());
        $this->vatSnapshot = $snapshot;
    }

    public function invalidateVatSnapshot(): void
    {
        $this->vatSnapshot = null;
    }

    public function hasPricingSnapshot(): bool
    {
        return $this->vatSnapshot !== null;
    }

    public function hasUpToDateVatSnapshot(): bool
    {
        if (! $this->vatSnapshot) {
            return false;
        }

        if ($this->hasFrozenPricing()) {
            return true;
        }

        if ($this->vatSnapshot->getPricingFingerprint() === null) {
            return false;
        }

        try {
            $this->vatSnapshot->assertMatchesTotalExcl($this->getTotalExcl());
            $this->vatSnapshot->assertMatchesPricingFingerprint($this->getPricingFingerprint());

            return true;
        } catch (\LogicException) {
            return false;
        }
    }

    protected function initializeVatSnapshotFromState(array $state): void
    {
        try {
            $vatLinesData = json_decode($state['vat_lines'], true);

            if (! is_array($vatLinesData)) {
                throw new \InvalidArgumentException('Stored VAT lines must be a JSON array.');
            }

            $vatLines = array_map(fn ($vatLineData) => new VatAllocatedLine(
                Money::EUR($vatLineData['taxable_base']),
                Money::EUR($vatLineData['vat_amount']),
                VatPercentage::fromString($vatLineData['vat_percentage']),
            ), $vatLinesData);

            $this->vatSnapshot = OrderVatSnapshot::fromState(
                vatLines: $vatLines,
                subtotalExcl: Money::EUR($state['subtotal_excl']),
                subtotalIncl: Money::EUR($state['subtotal_incl']),
                shippingExcl: Money::EUR($state['shipping_cost_excl']),
                shippingIncl: Money::EUR($state['shipping_cost_incl']),
                paymentExcl: Money::EUR($state['payment_cost_excl']),
                paymentIncl: Money::EUR($state['payment_cost_incl']),
                discountExcl: Money::EUR($state['discount_excl']),
                discountIncl: Money::EUR($state['discount_incl']),
                totalExcl: Money::EUR($state['total_excl']),
                totalVat: Money::EUR($state['total_vat']),
                totalIncl: Money::EUR($state['total_incl']),
                pricingFingerprint: $state['pricing_fingerprint'] ?? $state['vat_calculation_fingerprint'] ?? null,
            );
        } catch (\Throwable) {
            $this->vatSnapshot = null;
        }
    }

    protected function getOrderTotalsState(): array
    {
        if (! $this->vatSnapshot) {
            throw new VatSnapshotNotCalculated('Cannot get order totals state when VAT snapshot is not calculated.');
        }

        if (! $this->hasFrozenPricing()) {
            $this->vatSnapshot->assertMatchesTotalExcl($this->getTotalExcl());
            $this->vatSnapshot->assertMatchesPricingFingerprint($this->getPricingFingerprint());
        }

        return [
            'subtotal_excl' => $this->vatSnapshot->getSubtotalExcl()->getAmount(),
            'subtotal_incl' => $this->vatSnapshot->getSubtotalIncl()->getAmount(),
            'shipping_cost_excl' => $this->vatSnapshot->getShippingExcl()->getAmount(),
            'shipping_cost_incl' => $this->vatSnapshot->getShippingIncl()->getAmount(),
            'payment_cost_excl' => $this->vatSnapshot->getPaymentExcl()->getAmount(),
            'payment_cost_incl' => $this->vatSnapshot->getPaymentIncl()->getAmount(),
            'discount_excl' => $this->vatSnapshot->getDiscountExcl()->getAmount(),
            'discount_incl' => $this->vatSnapshot->getDiscountIncl()->getAmount(),
            'total_excl' => $this->vatSnapshot->getTotalExcl()->getAmount(),
            'total_vat' => $this->vatSnapshot->getTotalVat()->getAmount(),
            'total_incl' => $this->vatSnapshot->getTotalIncl()->getAmount(),
            'vat_lines' => json_encode(array_map(fn (VatAllocatedLine $vatLine) => [
                'taxable_base' => $vatLine->getTaxableBase()->getAmount(),
                'vat_amount' => $vatLine->getVatAmount()->getAmount(),
                'vat_percentage' => $vatLine->getVatPercentage()->get(),
            ], $this->vatSnapshot->getVatLines())),
            'pricing_fingerprint' => $this->vatSnapshot->getPricingFingerprint(),
        ];
    }

    public function getPricingFingerprint(): string
    {
        $inputs = [];

        foreach ($this->getLines() as $line) {
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
            'shipping' => $this->getShippings(),
            'payment' => $this->getPayments(),
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

        foreach ($this->getDiscounts() as $discount) {
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
            'vat_exempt' => $this->isVatExempt(),
            'components' => $inputs,
        ], JSON_THROW_ON_ERROR));
    }

    public function getSubtotalExcl(): Money
    {
        if ($this->hasFrozenPricing() && $this->vatSnapshot) {
            return $this->vatSnapshot->getSubtotalExcl();
        }

        $subtotal = Money::EUR(0);

        foreach ($this->getLines() as $line) {
            $subtotal = $subtotal->add($line->getTotal()->getExcludingVat());
        }

        return $subtotal;
    }

    public function getSubtotalIncl(): Money
    {
        if ($this->hasFrozenPricing() && $this->vatSnapshot) {
            return $this->vatSnapshot->getSubtotalIncl();
        }

        $subtotal = Money::EUR(0);

        foreach ($this->getLines() as $line) {
            $subtotal = $subtotal->add($line->getTotal()->getIncludingVat());
        }

        return $subtotal;
    }

    public function getShippingCostExcl(): Money
    {
        if ($this->hasFrozenPricing() && $this->vatSnapshot) {
            return $this->vatSnapshot->getShippingExcl();
        }

        $total = Money::EUR(0);

        foreach ($this->getShippings() as $shipping) {
            $total = $total->add($shipping->getShippingCostTotal()->getExcludingVat());
        }

        return $total;
    }

    public function getPaymentCostExcl(): Money
    {
        if ($this->hasFrozenPricing() && $this->vatSnapshot) {
            return $this->vatSnapshot->getPaymentExcl();
        }

        $total = Money::EUR(0);

        foreach ($this->getPayments() as $payment) {
            $total = $total->add($payment->getPaymentCostTotal()->getExcludingVat());
        }

        return $total;
    }

    public function getDiscountTotalExcl(): Money
    {
        if ($this->hasFrozenPricing() && $this->vatSnapshot) {
            return $this->vatSnapshot->getDiscountExcl();
        }

        $total = Money::EUR(0);

        foreach ($this->getDiscounts() as $discount) {
            $total = $total->add($discount->getDiscountPrice()->getExcludingVat());
        }

        return $total;
    }

    public function getTotalExcl(): Money
    {
        if ($this->hasFrozenPricing() && $this->vatSnapshot) {
            return $this->vatSnapshot->getTotalExcl();
        }

        return $this->getSubtotalExcl()
            ->add($this->getShippingCostExcl())
            ->add($this->getPaymentCostExcl())
            ->subtract($this->getDiscountTotalExcl());
    }

    public function getShippingCostIncl(): Money
    {
        $this->assertVatSnapshotIsCurrent('shipping cost incl.');

        return $this->vatSnapshot->getShippingIncl();
    }

    public function getPaymentCostIncl(): Money
    {
        $this->assertVatSnapshotIsCurrent('payment cost incl.');

        return $this->vatSnapshot->getPaymentIncl();
    }

    public function getDiscountTotalIncl(): Money
    {
        $this->assertVatSnapshotIsCurrent('discount total incl.');

        return $this->vatSnapshot->getDiscountIncl();
    }

    public function getTotalVat(): Money
    {
        $this->assertVatSnapshotIsCurrent('total VAT');

        return $this->vatSnapshot->getTotalVat();
    }

    public function getTotalIncl(): Money
    {
        $this->assertVatSnapshotIsCurrent('total incl.');

        return $this->vatSnapshot->getTotalIncl();
    }

    public function getVatLines(): array
    {
        $this->assertVatSnapshotIsCurrent('VAT lines');

        return $this->vatSnapshot->getVatLines();
    }

    private function assertVatSnapshotIsCurrent(string $value): void
    {
        if (! $this->vatSnapshot) {
            throw new VatSnapshotNotCalculated('Cannot get '.$value.' when VAT snapshot is not calculated.');
        }

        if ($this->hasFrozenPricing()) {
            return;
        }

        $this->vatSnapshot->assertMatchesTotalExcl($this->getTotalExcl());
        $this->vatSnapshot->assertMatchesPricingFingerprint($this->getPricingFingerprint());
    }
}
