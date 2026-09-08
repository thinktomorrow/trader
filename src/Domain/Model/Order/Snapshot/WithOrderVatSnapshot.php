<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Snapshot;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Vat\VatAllocatedLine;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;
use Thinktomorrow\Trader\Domain\Model\Order\Exceptions\VatSnapshotNotCalculated;

trait WithOrderVatSnapshot
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
            $this->assertVatSnapshotMatchesCurrentPricing($this->vatSnapshot);

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
        $snapshot = $this->getCurrentVatSnapshot('order totals state');

        return [
            'subtotal_excl' => $snapshot->getSubtotalExcl()->getAmount(),
            'subtotal_incl' => $snapshot->getSubtotalIncl()->getAmount(),
            'shipping_cost_excl' => $snapshot->getShippingExcl()->getAmount(),
            'shipping_cost_incl' => $snapshot->getShippingIncl()->getAmount(),
            'payment_cost_excl' => $snapshot->getPaymentExcl()->getAmount(),
            'payment_cost_incl' => $snapshot->getPaymentIncl()->getAmount(),
            'discount_excl' => $snapshot->getDiscountExcl()->getAmount(),
            'discount_incl' => $snapshot->getDiscountIncl()->getAmount(),
            'total_excl' => $snapshot->getTotalExcl()->getAmount(),
            'total_vat' => $snapshot->getTotalVat()->getAmount(),
            'total_incl' => $snapshot->getTotalIncl()->getAmount(),
            'vat_lines' => json_encode(array_map(fn (VatAllocatedLine $vatLine) => [
                'taxable_base' => $vatLine->getTaxableBase()->getAmount(),
                'vat_amount' => $vatLine->getVatAmount()->getAmount(),
                'vat_percentage' => $vatLine->getVatPercentage()->get(),
            ], $snapshot->getVatLines())),
            'pricing_fingerprint' => $snapshot->getPricingFingerprint(),
        ];
    }

    protected function getFrozenVatSnapshot(): ?OrderVatSnapshot
    {
        return $this->hasFrozenPricing() ? $this->vatSnapshot : null;
    }

    protected function getCurrentVatSnapshot(string $value): OrderVatSnapshot
    {
        if (! $this->vatSnapshot) {
            throw new VatSnapshotNotCalculated('Cannot get '.$value.' when VAT snapshot is not calculated.');
        }

        if (! $this->hasFrozenPricing()) {
            $this->assertVatSnapshotMatchesCurrentPricing($this->vatSnapshot);
        }

        return $this->vatSnapshot;
    }

    private function assertVatSnapshotMatchesCurrentPricing(OrderVatSnapshot $snapshot): void
    {
        $snapshot->assertMatchesTotalExcl($this->getTotalExcl());
        $snapshot->assertMatchesPricingFingerprint($this->getPricingFingerprint());
    }
}
