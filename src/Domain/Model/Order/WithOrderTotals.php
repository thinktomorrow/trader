<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultTotalPrice;
use Thinktomorrow\Trader\Domain\Common\Price\DiscountPrice;
use Thinktomorrow\Trader\Domain\Common\Price\TotalPrice;
use Thinktomorrow\Trader\Domain\Common\Vat\VatAllocatedLine;
use Thinktomorrow\Trader\Domain\Common\Vat\VatAllocatedTotalPrice;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\GetValidatedTotalDiscountPrice;

trait WithOrderTotals
{
    private(set) Money $subtotalExcl;
    private(set) Money $subtotalIncl;
    private(set) Money $shippingExcl;
    private(set) Money $shippingIncl;
    private(set) Money $paymentExcl;
    private(set) Money $paymentIncl;
    private(set) Money $discountExcl;
    private(set) Money $discountIncl;
    private(set) Money $totalExcl;
    private(set) Money $totalVat;
    private(set) Money $totalIncl;

    /** @var VatAllocatedLine[] */
    private(set) array $vatLines = [];

    public function getSubTotal(): TotalPrice
    {
        throw new \Exception('getSubTiotal is deprecated, use getSubTotalExcl or getSubTotalIncl instead.');

        // TODO: calculate subtotal in application layer...
        $subtotal = DefaultTotalPrice::zero();

        foreach ($this->lines as $line) {
            $subtotal = $subtotal->add($line->getTotal());
        }

        return $subtotal;
    }

    public function getTotal(): TotalPrice
    {
        throw new \Exception('getTotal is deprecated, use getTotalExcl or getTotalIncl instead.');
        return $this->getSubTotal()
            ->subtract($this->getTotalDiscountPrice())
            ->add($this->getShippingCost())
            ->add($this->getPaymentCost());
    }

    public function getTaxTotal(): Money
    {
        throw new \Exception('getTaxTotal is deprecated, use getTotalVat instead.');

        // TODO: vat Allocator implementation
        // exacte VAT percentages
        //pro-rata verdeling
        //afrondingsregels
        //grouping per VAT rate
        //invoice-level VAT logica indien nodig

        return Money::zero();

        //        return $this->getTotal()->getVatTotal();
    }

    /**
     * This is the discount total that is applied on the entire order (not per item).
     * Note that this is not a sum of all item discounts, but specifically the
     * order discounts total as calculated based on the subtotal.
     */
    public function getTotalDiscountPrice(): DiscountPrice
    {
        throw new \Exception('getTotalDiscountPrice is deprecated, use getDiscountTotalExcl or getDiscountTotalIncl instead.');

        // TODO: this should be calculated in application layer...

        return GetValidatedTotalDiscountPrice::get($this->getSubTotal(), $this);
    }

    public function getShippingCost(): TotalPrice
    {
        throw new \Exception('getShippingCost is deprecated, use getShippingCostExcl or getShippingCostIncl instead.');

        $shippingCost = DefaultTotalPrice::zero();

        foreach ($this->shippings as $shipping) {
            $shippingCost = $shippingCost->add($shipping->getShippingCost());
        }

        return $shippingCost;
    }

    public function getPaymentCost(): TotalPrice
    {
        throw new \Exception('getPaymentCost is deprecated, use getPaymentCostExcl or getPaymentCostIncl instead.');

        $paymentCost = DefaultTotalPrice::zero();

        foreach ($this->payments as $payment) {
            $paymentCost = $paymentCost->add($payment->getPaymentCost());
        }

        return $paymentCost;
    }

    public function applySubtotalTotals(VatAllocatedTotalPrice $subtotal): void
    {
        $this->subtotalExcl = $subtotal->getTotalExcludingVat();
        $this->subtotalIncl = $subtotal->getTotalIncludingVat();
    }

    public function applyServiceTotals(VatAllocatedTotalPrice $shippingCost, VatAllocatedTotalPrice $paymentCost): void
    {
        $this->shippingExcl = $shippingCost->getTotalExcludingVat();
        $this->shippingIncl = $shippingCost->getTotalIncludingVat();
        $this->paymentExcl = $paymentCost->getTotalExcludingVat();
        $this->paymentIncl = $paymentCost->getTotalIncludingVat();
    }

    public function applyDiscountTotals(VatAllocatedTotalPrice $discount): void
    {
        $this->discountExcl = $discount->getTotalExcludingVat();
        $this->discountIncl = $discount->getTotalIncludingVat();
    }

    public function applyTotals(VatAllocatedTotalPrice $allocation): void
    {
        // For totals we keep the vat lines breakdown
        $this->vatLines = $allocation->getVatLines();

        // Totals
        $this->totalExcl = $allocation->getTotalExcludingVat();
        $this->totalVat = $allocation->getTotalVat();
        $this->totalIncl = $allocation->getTotalIncludingVat();
    }
}
