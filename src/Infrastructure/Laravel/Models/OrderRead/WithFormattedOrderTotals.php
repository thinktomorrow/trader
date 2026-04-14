<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\OrderRead;

use Thinktomorrow\Trader\Application\Common\RendersMoney;

trait WithFormattedOrderTotals
{
    use RendersMoney;

    public function getFormattedSubtotalExcl(): string
    {
        return $this->renderMoney(
            $this->subtotalExcl,
            $this->getLocale()
        );
    }

    public function getFormattedSubtotalIncl(): string
    {
        return $this->renderMoney(
            $this->subtotalIncl,
            $this->getLocale()
        );
    }

    public function getFormattedShippingCostExcl(): string
    {
        return $this->renderMoney(
            $this->shippingExcl,
            $this->getLocale()
        );
    }

    public function getFormattedShippingCostIncl(): string
    {
        return $this->renderMoney(
            $this->shippingIncl,
            $this->getLocale()
        );
    }

    public function getFormattedPaymentCostExcl(): string
    {
        return $this->renderMoney(
            $this->paymentExcl,
            $this->getLocale()
        );
    }

    public function getFormattedPaymentCostIncl(): string
    {
        return $this->renderMoney(
            $this->paymentIncl,
            $this->getLocale()
        );
    }

    public function getFormattedDiscountTotalExcl(): string
    {
        return $this->renderMoney(
            $this->discountExcl,
            $this->getLocale()
        );
    }

    public function getFormattedDiscountTotalIncl(): string
    {
        return $this->renderMoney(
            $this->discountIncl,
            $this->getLocale()
        );
    }

    public function getFormattedTotalExcl(): string
    {
        return $this->renderMoney(
            $this->totalExcl,
            $this->getLocale()
        );
    }

    public function getFormattedTotalVat(): string
    {
        return $this->renderMoney(
            $this->totalVat,
            $this->getLocale()
        );
    }

    public function getFormattedTotalIncl(): string
    {
        return $this->renderMoney(
            $this->totalIncl,
            $this->getLocale()
        );
    }
}
