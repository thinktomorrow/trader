<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order;

use Money\Money;

trait WithOrderTotals
{
    public function getSubtotalExcl(): Money
    {
        if ($snapshot = $this->getFrozenVatSnapshot()) {
            return $snapshot->getSubtotalExcl();
        }

        $subtotal = Money::EUR(0);

        foreach ($this->getLines() as $line) {
            $subtotal = $subtotal->add($line->getTotal()->getExcludingVat());
        }

        return $subtotal;
    }

    public function getSubtotalIncl(): Money
    {
        if ($snapshot = $this->getFrozenVatSnapshot()) {
            return $snapshot->getSubtotalIncl();
        }

        $subtotal = Money::EUR(0);

        foreach ($this->getLines() as $line) {
            $subtotal = $subtotal->add($line->getTotal()->getIncludingVat());
        }

        return $subtotal;
    }

    public function getShippingCostExcl(): Money
    {
        if ($snapshot = $this->getFrozenVatSnapshot()) {
            return $snapshot->getShippingExcl();
        }

        $total = Money::EUR(0);

        foreach ($this->getShippings() as $shipping) {
            $total = $total->add($shipping->getShippingCostTotal()->getExcludingVat());
        }

        return $total;
    }

    public function getPaymentCostExcl(): Money
    {
        if ($snapshot = $this->getFrozenVatSnapshot()) {
            return $snapshot->getPaymentExcl();
        }

        $total = Money::EUR(0);

        foreach ($this->getPayments() as $payment) {
            $total = $total->add($payment->getPaymentCostTotal()->getExcludingVat());
        }

        return $total;
    }

    public function getDiscountTotalExcl(): Money
    {
        if ($snapshot = $this->getFrozenVatSnapshot()) {
            return $snapshot->getDiscountExcl();
        }

        $total = Money::EUR(0);

        foreach ($this->getDiscounts() as $discount) {
            $total = $total->add($discount->getDiscountPrice()->getExcludingVat());
        }

        return $total;
    }

    public function getTotalExcl(): Money
    {
        if ($snapshot = $this->getFrozenVatSnapshot()) {
            return $snapshot->getTotalExcl();
        }

        return $this->getSubtotalExcl()
            ->add($this->getShippingCostExcl())
            ->add($this->getPaymentCostExcl())
            ->subtract($this->getDiscountTotalExcl());
    }

    public function getShippingCostIncl(): Money
    {
        return $this->getCurrentVatSnapshot('shipping cost incl.')->getShippingIncl();
    }

    public function getPaymentCostIncl(): Money
    {
        return $this->getCurrentVatSnapshot('payment cost incl.')->getPaymentIncl();
    }

    public function getDiscountTotalIncl(): Money
    {
        return $this->getCurrentVatSnapshot('discount total incl.')->getDiscountIncl();
    }

    public function getTotalVat(): Money
    {
        return $this->getCurrentVatSnapshot('total VAT')->getTotalVat();
    }

    public function getTotalIncl(): Money
    {
        return $this->getCurrentVatSnapshot('total incl.')->getTotalIncl();
    }

    public function getVatLines(): array
    {
        return $this->getCurrentVatSnapshot('VAT lines')->getVatLines();
    }
}
