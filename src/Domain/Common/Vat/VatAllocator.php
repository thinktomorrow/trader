<?php

namespace Thinktomorrow\Trader\Domain\Common\Vat;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Model\Order\Order;

/**
 * The VAT Allocator turns an Order into:
 * - Per-VAT-rate taxable bases
 * - Per-VAT-rate VAT amounts
 * - A final VatAllocatedTotalPrice (excl, vat, incl)
 *
 * This is the final/legal VAT calculation used for:
 * - invoice totals
 * - bookkeeping exports
 * - definitive order totals
 *
 * ref: https://www.sendcloud.com/be/btw-over-verzendkosten-hoe-zit-het-precies/
 */
final class VatAllocator
{
    public function __construct(private ProRateAllocator $proRateAllocator)
    {
    }

    public function allocate(Order $order): VatAllocatedTotalPrice
    {
        /**
         * STEP 1 — Collect item taxable bases grouped by VAT percentage
         *
         * [
         *   '21' => Money(10000),
         *   '6'  => Money(5000)
         * ]
         */
        $itemTotals = $this->collectItemTotalsPerVat($order);

        /**
         * STEP 2 — Calculate the total EXCL VAT of all product items
         * (This forms the distribution base for service costs + discounts)
         */
        $itemsExclTotal = $this->sum($itemTotals);

        if ($itemsExclTotal->isZero()) {
            // Edge case: order without taxable items
            return new VatAllocatedTotalPrice([], Cash::zero(), Cash::zero(), Cash::zero());
        }

        /**
         * STEP 3 — Add service costs (shipping + payment) EXCL VAT
         */
        $servicesExcl = $order->getShippingCost()->getExcludingVat()
            ->add($order->getPaymentCost()->getExcludingVat());

        /**
         * STEP 4 — Subtract order-level discounts EXCL VAT
         */
        $orderDiscountExcl = $order->getTotalDiscountPrice()->getExcludingVat();

        /**
         * STEP 5 — Distribute service costs across VAT groups (pro-rata)
         */
        $allocatedServices = $this->proRateAllocator->allocate($itemTotals, $servicesExcl);

        /**
         * STEP 6 — Distribute order discount across VAT groups (pro-rata)
         */
        $allocatedDiscounts = $this->proRateAllocator->allocate($itemTotals, $orderDiscountExcl);

        /**
         * STEP 7 — Build final taxable base per VAT group:
         *
         * taxable = items + allocated services – allocated discount
         */
        $finalTaxableBases = [];
        foreach ($itemTotals as $rate => $itemsExcl) {
            $servicePart = $allocatedServices[$rate];
            $discountPart = $allocatedDiscounts[$rate];

            $finalTaxableBases[$rate] = $itemsExcl
                ->add($servicePart)
                ->subtract($discountPart);
        }

        /**
         * STEP 8 — Compute VAT per group and total
         */
        $vatLines = [];
        $totalExcl = Cash::zero();
        $totalVat = Cash::zero();
        $totalIncl = Cash::zero();

        foreach ($finalTaxableBases as $rate => $taxableBase) {
            $vatPercentage = VatPercentage::fromString($rate);

            $vatAmount = Cash::from($taxableBase)
                ->addPercentage($vatPercentage->toPercentage())
                ->subtract($taxableBase);

            $vatLines[] = new VatAllocatedLine(
                $taxableBase,
                $vatAmount,
                $vatPercentage
            );

            $totalExcl = $totalExcl->add($taxableBase);
            $totalVat = $totalVat->add($vatAmount);
            $totalIncl = $totalIncl->add($taxableBase)->add($vatAmount);
        }

        return new VatAllocatedTotalPrice(
            $vatLines,
            $totalExcl,
            $totalVat,
            $totalIncl
        );
    }

    private function collectItemTotalsPerVat(Order $order): array
    {
        $results = [];

        foreach ($order->getLines() as $line) {
            $itemPrice = $line->getTotal(); // ItemPrice (item-level)
            $vatRate = $line->getTotal()->getVatPercentage()->get();

            if (! isset($results[$vatRate])) {
                $results[$vatRate] = Cash::zero();
            }

            $results[$vatRate] = $results[$vatRate]
                ->add($itemPrice->getExcludingVat());
        }

        return $results;
    }

    private function sum(array $amounts): Money
    {
        $sum = Cash::zero();

        foreach ($amounts as $money) {
            $sum = $sum->add($money);
        }

        return $sum;
    }
}
