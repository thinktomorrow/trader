<?php

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\OrderRead;

trait WithFormattedLineTotals
{
    public function getFormattedUnitPriceExcl(): string
    {
        return $this->renderMoney($this->getUnitPriceExcl(), $this->getLocale());
    }

    public function getFormattedUnitPriceIncl(): string
    {
        return $this->renderMoney($this->getUnitPriceIncl(), $this->getLocale());
    }

    public function getFormattedDiscountedUnitPriceExcl(): string
    {
        return $this->renderMoney($this->getDiscountedUnitPriceExcl(), $this->getLocale());
    }

    public function getFormattedDiscountedUnitPriceIncl(): string
    {
        return $this->renderMoney($this->getDiscountedUnitPriceIncl(), $this->getLocale());
    }

    public function getFormattedDiscountPriceExcl(): string
    {
        return $this->renderMoney($this->getDiscountPriceExcl(), $this->getLocale());
    }

    public function getFormattedDiscountPriceIncl(): string
    {
        return $this->renderMoney($this->getDiscountPriceIncl(), $this->getLocale());
    }

    public function getDiscountPercentage(): int
    {
        $unitPrice = $this->getUnitPriceExcl()->getAmount();
        $salePrice = $this->getDiscountedUnitPriceExcl()->getAmount();

        if ($unitPrice == 0) {
            return 0;
        }

        return (int)ceil((($unitPrice - $salePrice) / $unitPrice) * 100);
    }

    public function getFormattedTotalPriceExcl(): string
    {
        return $this->renderMoney($this->getTotalPriceExcl(), $this->getLocale());
    }

    public function getFormattedTotalPriceIncl(): string
    {
        return $this->renderMoney($this->getTotalPriceIncl(), $this->getLocale());
    }

    public function getFormattedSubtotalPriceExcl(): string
    {
        $subtotal = $this->getTotalPriceExcl()->subtract($this->getDiscountPriceExcl());

        return $this->renderMoney($subtotal, $this->getLocale());
    }

    public function getFormattedSubtotalPriceIncl(): string
    {
        $subtotal = $this->getTotalPriceIncl()->subtract($this->getDiscountPriceIncl());

        return $this->renderMoney($subtotal, $this->getLocale());
    }

    public function getFormattedTotalVat(): string
    {
        return $this->renderMoney(
            $this->getTotalVat(),
            $this->getLocale()
        );
    }

    public function getFormattedVatRate(): string
    {
        return $this->vatRate->get();
    }

    //    public function getFormattedUnitPrice(): string
    //    {
    //        return $this->renderMoney(
    //            $this->include_tax ? $this->getUnitPriceIncl() : $this->getUnitPriceExcl(),
    //            $this->getLocale()
    //        );
    //    }
    //
    //    public function getFormattedDiscountPrice(): string
    //    {
    //        return $this->renderMoney(
    //            $this->include_tax ? $this->getDiscountPriceIncl() : $this->getDiscountPriceExcl(),
    //            $this->getLocale()
    //        );
    //    }
    //
    //    public function getFormattedTotalPrice(): string
    //    {
    //        return $this->renderMoney(
    //            $this->include_tax ? $this->getTotalPriceIncl() : $this->getTotalPriceExcl(),
    //            $this->getLocale()
    //        );
    //    }
    //
    //    public function getFormattedSubtotalPrice(): string
    //    {
    //        $subtotal = $this->include_tax
    //            ? $this->getTotalPriceIncl()->subtract($this->getDiscountPriceIncl())
    //            : $this->getTotalPriceExcl()->subtract($this->getDiscountPriceExcl());
    //
    //        return $this->renderMoney($subtotal, $this->getLocale());
    //    }
}
