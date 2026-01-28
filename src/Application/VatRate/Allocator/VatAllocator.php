<?php

namespace Thinktomorrow\Trader\Application\VatRate\Allocator;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Vat\VatAllocatedLine;
use Thinktomorrow\Trader\Domain\Common\Vat\VatAllocatedTotalPrice;
use Thinktomorrow\Trader\Domain\Common\Vat\VatAllocatedTotalPrices;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;
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
 * https://help.eenvoudigfactureren.be/support/solutions/articles/101000447468-afrondingsverschil-btw-incl-excl-btw
 */
final class VatAllocator
{
    public function __construct(private ProRateAllocator $proRateAllocator)
    {
    }

    /**
     * @return VatAllocatedTotalPrices
     */
    public function allocate(Order $order, Money $shipping, Money $payment, Money $discount): VatAllocatedTotalPrices
    {
        // Item bases per VAT
        $itemTotals = $this->collectItemTotalsPerVat($order);

        if ($this->sumExcl($itemTotals)->isZero()) {
            $zeroItems = new VatAllocatedTotalPrice([], Cash::zero(), Cash::zero(), Cash::zero());

            // Service-only: treat as 0% VAT (incl == excl, vat == 0)
            $shippingTotal = new VatAllocatedTotalPrice([], $shipping, Cash::zero(), $shipping);
            $paymentTotal = new VatAllocatedTotalPrice([], $payment, Cash::zero(), $payment);
            $discountTotal = new VatAllocatedTotalPrice([], $discount, Cash::zero(), $discount);

            $totalExcl = $shipping->add($payment)->subtract($discount);
            $total = new VatAllocatedTotalPrice([], $totalExcl, Cash::zero(), $totalExcl);

            return new VatAllocatedTotalPrices(
                items: $zeroItems,
                shipping: $shippingTotal,
                payment: $paymentTotal,
                discounts: $discountTotal,
                total: $total,
            );
        }

        // Allocate excl amounts - Distribute service costs and order-level discounts across VAT groups (pro-rata)
        $shippingAlloc = $this->proRateAllocator->allocate(
            $itemTotals,
            $shipping
        );

        $paymentAlloc = $this->proRateAllocator->allocate(
            $itemTotals,
            $payment
        );

        $discountAlloc = $this->proRateAllocator->allocate(
            $itemTotals,
            $discount
        );

        $itemsTotal = $this->buildAllocatedItemTotal($itemTotals);
        $shippingTotal = $this->buildAllocatedServiceTotal($shippingAlloc);
        $paymentTotal = $this->buildAllocatedServiceTotal($paymentAlloc);
        $discountTotal = $this->buildAllocatedServiceTotal($discountAlloc);

        // 4) Build all allocated totals
        return new VatAllocatedTotalPrices(
            items: $itemsTotal,
            shipping: $shippingTotal,
            payment: $paymentTotal,
            discounts: $discountTotal,
            total: $this->buildAllocatedTotal($itemsTotal, $shippingTotal, $paymentTotal, $discountTotal)
        );
    }

    private function buildAllocatedItemTotal(array $itemTotalsPerRate): VatAllocatedTotalPrice
    {
        $vatLines = [];
        $totalExcl = Cash::zero();
        $totalIncl = Cash::zero();

        foreach ($itemTotalsPerRate as $rate => $totalPrice) {
            $totalExcl = $totalExcl->add($totalPrice->getExcludingVat());
            $totalIncl = $totalIncl->add($totalPrice->getIncludingVat());

            $vatPercentage = VatPercentage::fromString($rate);
            $vat = $totalPrice->getIncludingVat()->subtract($totalPrice->getExcludingVat());

            $vatLines[] = new VatAllocatedLine($totalPrice->getExcludingVat(), $vat, $vatPercentage);
        }

        $totalVat = $totalIncl->subtract($totalExcl);

        return new VatAllocatedTotalPrice(
            $vatLines,
            $totalExcl,
            $totalVat,
            $totalIncl
        );
    }

    /**
     * Build the VatAllocatedTotalPrice from bases per VAT rate.
     *
     * In e-commerce, VAT rounding is resolved at the final aggregation level.
     * The customer-facing including-VAT total can be set as authoritative.
     * Any rounding difference is then absorbed by the VAT amount.
     *
     * @param array $amountsExclPerRate
     * @param Money|null $authoritativeIncl
     * @return VatAllocatedTotalPrice
     */
    private function buildAllocatedServiceTotal(array $amountsExclPerRate): VatAllocatedTotalPrice
    {
        $vatLines = [];
        $totalExcl = Cash::zero();
        $totalVat = Cash::zero();

        foreach ($amountsExclPerRate as $rate => $base) {

            $vatPercentage = VatPercentage::fromString($rate);

            $vat = Cash::from($base)
                ->addPercentage($vatPercentage->toPercentage())
                ->subtract($base);

            $vatLines[] = new VatAllocatedLine($base, $vat, $vatPercentage);

            $totalExcl = $totalExcl->add($base);
            $totalVat = $totalVat->add($vat);
        }

        $totalIncl = $totalExcl->add($totalVat);

        return new VatAllocatedTotalPrice(
            $vatLines,
            $totalExcl,
            $totalVat,
            $totalIncl
        );
    }

    private function buildAllocatedTotal(VatAllocatedTotalPrice $itemTotal, VatAllocatedTotalPrice $shippingTotal, VatAllocatedTotalPrice $paymentTotal, VatAllocatedTotalPrice $orderDiscountTotal): VatAllocatedTotalPrice
    {
        $totalExcl = Cash::zero()
            ->add($itemTotal->getTotalExcludingVat())
            ->add($shippingTotal->getTotalExcludingVat())
            ->add($paymentTotal->getTotalExcludingVat())
            ->subtract($orderDiscountTotal->getTotalExcludingVat());

        $totalIncl = Cash::zero()
            ->add($itemTotal->getTotalIncludingVat())
            ->add($shippingTotal->getTotalIncludingVat())
            ->add($paymentTotal->getTotalIncludingVat())
            ->subtract($orderDiscountTotal->getTotalIncludingVat());

        $totalVat = Cash::zero()
            ->add($itemTotal->getTotalVat())
            ->add($shippingTotal->getTotalVat())
            ->add($paymentTotal->getTotalVat())
            ->subtract($orderDiscountTotal->getTotalVat());

        $vatLines = [];

        foreach ([$itemTotal->getVatLines(), $shippingTotal->getVatLines(), $paymentTotal->getVatLines()] as $vatLinesPart) {
            foreach ($vatLinesPart as $vatLine) {
                $vatPercentage = $vatLine->getVatPercentage()->get();

                if (!isset($vatLines[$vatPercentage])) {
                    $vatLines[$vatPercentage] = $vatLine;
                } else {
                    $vatLines[$vatPercentage] = $vatLines[$vatPercentage]->add($vatLine);
                }
            }
        }

        foreach ($orderDiscountTotal->getVatLines() as $vatLine) {
            $vatPercentage = $vatLine->getVatPercentage()->get();

            if (isset($vatLines[$vatPercentage])) {
                $vatLines[$vatPercentage] = $vatLines[$vatPercentage]->subtract($vatLine);
            }
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

            if (!isset($results[$vatRate])) {
                $results[$vatRate] = $itemPrice;
            } else {
                $results[$vatRate] = $results[$vatRate]->add($itemPrice);
            }
        }

        return $results;
    }

    private function sumExcl(array $itemPrices): Money
    {
        $sum = Cash::zero();

        foreach ($itemPrices as $price) {
            $sum = $sum->add($price->getExcludingVat());
        }

        return $sum;
    }

    private function sumIncl(array $itemPrices): Money
    {
        $sum = Cash::zero();

        foreach ($itemPrices as $price) {
            $sum = $sum->add($price->getIncludingVat());
        }

        return $sum;
    }
}
