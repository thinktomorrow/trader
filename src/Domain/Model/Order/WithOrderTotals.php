<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Vat\VatAllocatedLine;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;
use Thinktomorrow\Trader\Domain\Model\Order\Exceptions\VatSnapshotNotCalculated;

trait WithOrderTotals
{
    protected ?OrderVatSnapshot $vatSnapshot = null;

    public function applyVatSnapshot(OrderVatSnapshot $snapshot): void
    {
        $snapshot->assertMatchesTotalExcl($this->getTotalExcl());
        $this->vatSnapshot = $snapshot;
    }

    public function invalidateVatSnapshot(): void
    {
        $this->vatSnapshot = null;
    }

    public function hasUpToDateVatSnapshot(): bool
    {
        if (!$this->vatSnapshot) {
            return false;
        }

        try {
            $this->vatSnapshot->assertMatchesTotalExcl($this->getTotalExcl());

            return true;
        } catch (\LogicException) {
            return false;
        }
    }

    protected function initializeVatSnapshotFromState(array $state): void
    {
        $vatLines = array_map(fn($vatLineData) => new VatAllocatedLine(
            Money::EUR($vatLineData['taxable_base']),
            Money::EUR($vatLineData['vat_amount']),
            VatPercentage::fromString($vatLineData['vat_percentage']),
        ), json_decode($state['vat_lines'], true));

        $this->vatSnapshot = OrderVatSnapshot::fromState(
            vatLines: $vatLines,
            shippingIncl: Money::EUR($state['shipping_cost_incl']),
            paymentIncl: Money::EUR($state['payment_cost_incl']),
            discountIncl: Money::EUR($state['discount_incl']),
            totalVat: Money::EUR($state['total_vat']),
            totalIncl: Money::EUR($state['total_incl']),
        );
    }

    protected function getOrderTotalsState(): array
    {
        if (!$this->vatSnapshot) {
            throw new VatSnapshotNotCalculated('Cannot get order totals state when VAT snapshot is not calculated.');
        }

        $this->vatSnapshot->assertMatchesTotalExcl($this->getTotalExcl());

        return [
            // From Order totals calculations
            'subtotal_excl' => $this->getSubtotalExcl()->getAmount(),
            'subtotal_incl' => $this->getSubtotalIncl()->getAmount(),
            'shipping_cost_excl' => $this->getShippingCostExcl()->getAmount(),
            'payment_cost_excl' => $this->getPaymentCostExcl()->getAmount(),
            'discount_excl' => $this->getDiscountTotalExcl()->getAmount(),
            'total_excl' => $this->getTotalExcl()->getAmount(),

            // From VatSnapshot (calculated via VatAllocator)
            'shipping_cost_incl' => $this->vatSnapshot->getShippingIncl()->getAmount(),
            'payment_cost_incl' => $this->vatSnapshot->getPaymentIncl()->getAmount(),
            'discount_incl' => $this->vatSnapshot->getDiscountIncl()->getAmount(),
            'total_vat' => $this->vatSnapshot->getTotalVat()->getAmount(),
            'total_incl' => $this->vatSnapshot->getTotalIncl()->getAmount(),
            'vat_lines' => json_encode(array_map(fn(VatAllocatedLine $vatLine) => [
                'taxable_base' => $vatLine->getTaxableBase()->getAmount(),
                'vat_amount' => $vatLine->getVatAmount()->getAmount(),
                'vat_percentage' => $vatLine->getVatPercentage()->get(),
            ], $this->vatSnapshot->getVatLines())),
        ];
    }

    public function getSubtotalExcl(): Money
    {
        $subtotal = Money::EUR(0);

        foreach ($this->getLines() as $line) {
            $subtotal = $subtotal->add($line->getTotal()->getExcludingVat());
        }

        return $subtotal;
    }

    public function getSubtotalIncl(): Money
    {
        $subtotal = Money::EUR(0);

        foreach ($this->getLines() as $line) {
            $subtotal = $subtotal->add($line->getTotal()->getIncludingVat());
        }

        return $subtotal;
    }

    public function getShippingCostExcl(): Money
    {
        $total = Money::EUR(0);

        foreach ($this->getShippings() as $shipping) {
            $total = $total->add($shipping->getShippingCost()->getExcludingVat());
        }

        return $total;
    }

    public function getPaymentCostExcl(): Money
    {
        $total = Money::EUR(0);

        foreach ($this->getPayments() as $payment) {
            $total = $total->add($payment->getPaymentCost()->getExcludingVat());
        }

        return $total;
    }

    public function getDiscountTotalExcl(): Money
    {
        $total = Money::EUR(0);

        foreach ($this->getDiscounts() as $discount) {
            $total = $total->add($discount->getDiscountPrice()->getExcludingVat());
        }

        return $total;
    }

    public function getTotalExcl(): Money
    {
        return $this->getSubtotalExcl()
            ->add($this->getShippingCostExcl())
            ->add($this->getPaymentCostExcl())
            ->subtract($this->getDiscountTotalExcl());
    }

    public function getShippingCostIncl(): Money
    {
        if (!$this->vatSnapshot) {
            throw new VatSnapshotNotCalculated('Cannot get shipping cost incl. when VAT snapshot is not calculated.');
        }

        return $this->vatSnapshot->getShippingIncl();
    }

    public function getPaymentCostIncl(): Money
    {
        if (!$this->vatSnapshot) {
            throw new VatSnapshotNotCalculated('Cannot get payment cost incl. when VAT snapshot is not calculated.');
        }

        return $this->vatSnapshot->getPaymentIncl();
    }

    public function getDiscountTotalIncl(): Money
    {
        if (!$this->vatSnapshot) {
            throw new VatSnapshotNotCalculated('Cannot get discount total incl. when VAT snapshot is not calculated.');
        }

        return $this->vatSnapshot->getDiscountIncl();
    }

    public function getTotalVat(): Money
    {
        if (!$this->vatSnapshot) {
            throw new VatSnapshotNotCalculated('Cannot get total VAT when VAT snapshot is not calculated.');
        }

        return $this->vatSnapshot->getTotalVat();
    }

    public function getTotalIncl(): Money
    {
        if (!$this->vatSnapshot) {
            throw new VatSnapshotNotCalculated('Cannot get total incl. when VAT snapshot is not calculated.');
        }

        return $this->vatSnapshot->getTotalIncl();
    }

    public function getVatLines(): array
    {
        if (!$this->vatSnapshot) {
            throw new VatSnapshotNotCalculated('Cannot get VAT lines when VAT snapshot is not calculated.');
        }

        return $this->vatSnapshot->getVatLines();
    }
}
