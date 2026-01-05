<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order;

use Money\Money;

trait WithOrderTotals
{
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

    protected function getOrderTotalsState(): array
    {
        return [
            'subtotal_excl' => $this->subtotalExcl->getAmount(),
            'subtotal_incl' => $this->subtotalIncl->getAmount(),
            'shipping_cost_excl' => $this->shippingExcl->getAmount(),
            'shipping_cost_incl' => $this->shippingIncl->getAmount(),
            'payment_cost_excl' => $this->paymentExcl->getAmount(),
            'payment_cost_incl' => $this->paymentIncl->getAmount(),
            'discount_excl' => $this->discountExcl->getAmount(),
            'discount_incl' => $this->discountIncl->getAmount(),
            'total_excl' => $this->totalExcl->getAmount(),
            'total_vat' => $this->totalVat->getAmount(),
            'total_incl' => $this->totalIncl->getAmount(),
        ];
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
}
