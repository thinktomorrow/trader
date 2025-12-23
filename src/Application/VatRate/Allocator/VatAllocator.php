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

        if ($this->sum($itemTotals)->isZero()) {
            $zero = new VatAllocatedTotalPrice([], Cash::zero(), Cash::zero(), Cash::zero());

            return new VatAllocatedTotalPrices($zero, $zero, $zero, $zero, $zero);
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

        // Final taxable bases for TOTAL
        $totalBases = [];
        foreach ($itemTotals as $rate => $itemsExcl) {
            $totalBases[$rate] = $itemsExcl
                ->add($shippingAlloc[$rate])
                ->add($paymentAlloc[$rate])
                ->subtract($discountAlloc[$rate]);
        }

        // 4) Build all allocated totals
        return new VatAllocatedTotalPrices(
            items: $this->buildAllocatedTotal($itemTotals),
            shipping: $this->buildAllocatedTotal($shippingAlloc),
            payment: $this->buildAllocatedTotal($paymentAlloc),
            discounts: $this->buildAllocatedTotal($discountAlloc),
            total: $this->buildAllocatedTotal($totalBases),
        );
    }

    private function buildAllocatedTotal(array $basesPerRate): VatAllocatedTotalPrice
    {
        $vatLines = [];
        $totalExcl = Cash::zero();
        $totalVat = Cash::zero();
        $totalIncl = Cash::zero();

        foreach ($basesPerRate as $rate => $base) {
            $vatPercentage = VatPercentage::fromString($rate);

            $vat = Cash::from($base)
                ->addPercentage($vatPercentage->toPercentage())
                ->subtract($base);

            $vatLines[] = new VatAllocatedLine($base, $vat, $vatPercentage);

            $totalExcl = $totalExcl->add($base);
            $totalVat = $totalVat->add($vat);
            $totalIncl = $totalIncl->add($base)->add($vat);
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
