<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\OrderRead;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Vat\VatAllocatedLine;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;

trait WithImmutableOrderTotals
{
    /** @var VatAllocatedLine[] */
    private array $vatLines = [];

    protected Money $subtotalExcl;
    protected Money $subtotalIncl;
    protected Money $shippingExcl;
    protected Money $shippingIncl;
    protected Money $paymentExcl;
    protected Money $paymentIncl;
    protected Money $discountExcl;
    protected Money $discountIncl;
    protected Money $totalExcl;
    protected Money $totalVat;
    protected Money $totalIncl;

    protected function initializeOrderTotalsFromState(array $state): void
    {
        $this->vatLines = array_map(fn ($vatLineData) => new VatAllocatedLine(
            Money::EUR($vatLineData['taxable_base']),
            Money::EUR($vatLineData['vat_amount']),
            VatPercentage::fromString($vatLineData['vat_percentage']),
        ), json_decode($state['vat_lines'], true));

        $this->totalExcl = Money::EUR($state['total_excl']);
        $this->totalIncl = Money::EUR($state['total_incl']);
        $this->totalVat = Money::EUR($state['total_vat']);
        $this->subtotalExcl = Money::EUR($state['subtotal_excl']);
        $this->subtotalIncl = Money::EUR($state['subtotal_incl']);
        $this->discountExcl = Money::EUR($state['discount_excl']);
        $this->discountIncl = Money::EUR($state['discount_incl']);
        $this->shippingExcl = Money::EUR($state['shipping_cost_excl']);
        $this->shippingIncl = Money::EUR($state['shipping_cost_incl']);
        $this->paymentExcl = Money::EUR($state['payment_cost_excl']);
        $this->paymentIncl = Money::EUR($state['payment_cost_incl']);
    }

    protected function initializeEmptyOrderTotals(): void
    {
        $zero = Money::EUR(0);

        $this->totalExcl = $zero;
        $this->totalVat = $zero;
        $this->totalIncl = $zero;
        $this->subtotalExcl = $zero;
        $this->subtotalIncl = $zero;
        $this->discountExcl = $zero;
        $this->discountIncl = $zero;
        $this->shippingExcl = $zero;
        $this->shippingIncl = $zero;
        $this->paymentExcl = $zero;
        $this->paymentIncl = $zero;
    }

    public function getSubtotalExcl(): Money
    {
        return $this->subtotalExcl;
    }

    public function getSubtotalIncl(): Money
    {
        return $this->subtotalIncl;
    }

    public function getShippingCostExcl(): Money
    {
        return $this->shippingExcl;
    }

    public function getShippingCostIncl(): Money
    {
        return $this->shippingIncl;
    }

    public function getPaymentCostExcl(): Money
    {
        return $this->paymentExcl;
    }

    public function getPaymentCostIncl(): Money
    {
        return $this->paymentIncl;
    }

    public function getDiscountTotalExcl(): Money
    {
        return $this->discountExcl;
    }

    public function getDiscountTotalIncl(): Money
    {
        return $this->discountIncl;
    }

    public function getTotalExcl(): Money
    {
        return $this->totalExcl;
    }

    public function getTotalVat(): Money
    {
        return $this->totalVat;
    }

    public function getTotalIncl(): Money
    {
        return $this->totalIncl;
    }

    /** @return VatAllocatedLine[] */
    public function getVatLines(): array
    {
        return $this->vatLines;
    }
}
