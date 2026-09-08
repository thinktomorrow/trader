<?php

namespace Thinktomorrow\Trader\Application\VatRate\Allocator;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultItemPrice;
use Thinktomorrow\Trader\Domain\Common\Price\HasAuthoritativeAmount;
use Thinktomorrow\Trader\Domain\Common\Price\ItemPrice;
use Thinktomorrow\Trader\Domain\Common\Price\Price;
use Thinktomorrow\Trader\Domain\Common\Price\TaxMode;
use Thinktomorrow\Trader\Domain\Common\Price\VatApplicableAmount;
use Thinktomorrow\Trader\Domain\Common\Vat\VatAllocatedLine;
use Thinktomorrow\Trader\Domain\Common\Vat\VatAllocatedTotalPrice;
use Thinktomorrow\Trader\Domain\Common\Vat\VatAllocatedTotalPrices;
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
    private VatApplicableAmountAllocator $vatApplicableAmountAllocator;

    public function __construct(VatApplicableAmountAllocator|ProRateAllocator $allocator)
    {
        $this->vatApplicableAmountAllocator = $allocator instanceof VatApplicableAmountAllocator
            ? $allocator
            : new VatApplicableAmountAllocator($allocator);
    }

    /**
     * Allocate already aggregated shipping, payment and discount amounts over the order's VAT mix.
     * Plain Money inputs are treated as excluding-VAT authoritative for backwards compatibility.
     */
    public function allocate(Order $order, Money|VatApplicableAmount $shipping, Money|VatApplicableAmount $payment, Money|VatApplicableAmount $discount): VatAllocatedTotalPrices
    {
        // Item bases per VAT
        $itemBasesPerVatRate = $this->collectItemBasesPerVatRate($order);

        $itemsTotal = $this->buildAllocatedItemTotal($order);
        $shippingTotal = $this->vatApplicableAmountAllocator->allocate($itemBasesPerVatRate, $this->normalizeAmount($shipping));
        $paymentTotal = $this->vatApplicableAmountAllocator->allocate($itemBasesPerVatRate, $this->normalizeAmount($payment));
        $discountTotal = $this->vatApplicableAmountAllocator->allocate($itemBasesPerVatRate, $this->normalizeAmount($discount));

        return new VatAllocatedTotalPrices(
            items: $itemsTotal,
            shipping: $shippingTotal,
            payment: $paymentTotal,
            discounts: $discountTotal,
            total: $this->buildAllocatedTotal($itemsTotal, $shippingTotal, $paymentTotal, $discountTotal)
        );
    }

    /**
     * Allocate every persisted service and discount separately before combining their VAT lines.
     * This preserves per-component authority and rounding that would be lost by allocating one
     * pre-summed shipping, payment or discount amount.
     */
    public function allocateOrder(Order $order): VatAllocatedTotalPrices
    {
        $itemBasesPerVatRate = $this->collectItemBasesPerVatRate($order);
        $itemsTotal = $this->buildAllocatedItemTotal($order);
        $shippingCosts = [];
        $shippingDiscounts = [];
        $paymentCosts = [];
        $paymentDiscounts = [];
        $orderDiscountParts = [];

        foreach ($order->getShippings() as $shipping) {
            $shippingCosts[] = $this->allocateAuthoritativePrice($itemBasesPerVatRate, $shipping->getShippingCost());
            $shippingDiscounts[] = $this->allocateAuthoritativePrice($itemBasesPerVatRate, $shipping->getDiscountPrice());
        }

        foreach ($order->getPayments() as $payment) {
            $paymentCosts[] = $this->allocateAuthoritativePrice($itemBasesPerVatRate, $payment->getPaymentCost());
            $paymentDiscounts[] = $this->allocateAuthoritativePrice($itemBasesPerVatRate, $payment->getDiscountPrice());
        }

        foreach ($order->getDiscounts() as $discount) {
            $orderDiscountParts[] = $this->allocateAuthoritativePrice($itemBasesPerVatRate, $discount->getDiscountPrice());
        }

        $shippingTotal = $this->combineAllocatedTotals($shippingCosts, $shippingDiscounts);
        $paymentTotal = $this->combineAllocatedTotals($paymentCosts, $paymentDiscounts);
        $discountTotal = $this->combineAllocatedTotals($orderDiscountParts);

        return new VatAllocatedTotalPrices(
            items: $itemsTotal,
            shipping: $shippingTotal,
            payment: $paymentTotal,
            discounts: $discountTotal,
            total: $this->buildAllocatedTotal($itemsTotal, $shippingTotal, $paymentTotal, $discountTotal),
        );
    }

    /**
     * Resolve the taxable base of an amount against the current net item distribution.
     * Inclusive amounts cannot be reversed using one VAT percentage because an order may contain
     * multiple rates; exclusive amounts already provide their authoritative taxable base.
     */
    public function resolveVatApplicableAmountExcludingVat(Order $order, VatApplicableAmount $amount): Money
    {
        if ($amount->getTaxMode() === TaxMode::Exclusive) {
            return $amount->getAmount();
        }

        return $this->vatApplicableAmountAllocator->deriveExcludingVatFromIncludingVat(
            $this->collectItemBasesPerVatRate($order),
            $amount->getAmount(),
        );
    }

    /**
     * Preserve each line's own VAT and rounding, then group those definitive values by VAT rate.
     * Recalculating VAT from a grouped taxable base could produce a different rounded result.
     */
    private function buildAllocatedItemTotal(Order $order): VatAllocatedTotalPrice
    {
        /** @var array<string, VatAllocatedLine> $vatLinesPerRate */
        $vatLinesPerRate = [];

        foreach ($order->getLines() as $line) {
            $price = $line->getTotal();
            $rate = $price->getVatPercentage()->get();
            $vatLine = new VatAllocatedLine(
                $price->getExcludingVat(),
                $price->getVatTotal(),
                $price->getVatPercentage(),
            );

            $vatLinesPerRate[$rate] = isset($vatLinesPerRate[$rate])
                ? $vatLinesPerRate[$rate]->add($vatLine)
                : $vatLine;
        }

        uksort($vatLinesPerRate, fn (string|int $left, string|int $right): int => bccomp((string) $right, (string) $left, 6));

        $vatLines = [];
        $totalExcl = Cash::zero();
        $totalIncl = Cash::zero();

        foreach ($vatLinesPerRate as $vatLine) {
            $totalExcl = $totalExcl->add($vatLine->getTaxableBase());
            $totalIncl = $totalIncl->add($vatLine->getTotalIncludingVat());
            $vatLines[] = $vatLine;
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
     * Add item and service VAT lines and subtract discount VAT lines into one legal order total.
     */
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

                if (! isset($vatLines[$vatPercentage])) {
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

        // Stable descending rates keep snapshots and downstream exports independent of insertion order.
        uksort($vatLines, fn (string|int $left, string|int $right): int => bccomp((string) $right, (string) $left, 6));

        return new VatAllocatedTotalPrice(
            $vatLines,
            $totalExcl,
            $totalVat,
            $totalIncl
        );
    }

    /**
     * Collect net line totals after line discounts. These bases determine how services and global
     * order discounts are distributed over the order's VAT rates.
     *
     * @return array<string, ItemPrice>
     */
    private function collectItemBasesPerVatRate(Order $order): array
    {
        $results = [];

        foreach ($order->getLines() as $line) {
            $itemPrice = $line->getTotal();
            $vatRate = $itemPrice->getVatPercentage()->get();

            if (! isset($results[$vatRate])) {
                $results[$vatRate] = DefaultItemPrice::fromExcludingVat(
                    $itemPrice->getExcludingVat(),
                    $itemPrice->getVatPercentage(),
                );
            } else {
                $results[$vatRate] = DefaultItemPrice::fromExcludingVat(
                    $results[$vatRate]->getExcludingVat()->add($itemPrice->getExcludingVat()),
                    $itemPrice->getVatPercentage(),
                );
            }
        }

        uksort($results, fn (string|int $left, string|int $right): int => bccomp((string) $right, (string) $left, 6));

        return $results;
    }

    private function normalizeAmount(Money|VatApplicableAmount $amount): VatApplicableAmount
    {
        return $amount instanceof VatApplicableAmount ? $amount : VatApplicableAmount::excludingVat($amount);
    }

    /**
     * Allocate a component from its stored authoritative amount and resolved excluding-VAT base.
     * The excluding base must not be derived again, otherwise persisted component rounding can shift.
     *
     * @param  array<string, ItemPrice>  $itemBasesPerVatRate
     */
    private function allocateAuthoritativePrice(array $itemBasesPerVatRate, HasAuthoritativeAmount&Price $price): VatAllocatedTotalPrice
    {
        return $this->vatApplicableAmountAllocator->allocateResolved(
            $itemBasesPerVatRate,
            VatApplicableAmount::fromMoney($price->getAuthoritativeAmount(), $price->getTaxMode()),
            $price->getExcludingVat(),
        );
    }

    /**
     * Combine already allocated components per VAT rate. Positive totals are costs; negative totals
     * are discounts and are subtracted from both the taxable base and VAT amount.
     *
     * @param  VatAllocatedTotalPrice[]  $positiveTotals
     * @param  VatAllocatedTotalPrice[]  $negativeTotals
     */
    private function combineAllocatedTotals(array $positiveTotals, array $negativeTotals = []): VatAllocatedTotalPrice
    {
        /** @var array<string, VatAllocatedLine> $lines */
        $lines = [];
        $totalExcludingVat = Cash::zero();
        $totalVat = Cash::zero();

        foreach ($positiveTotals as $total) {
            $totalExcludingVat = $totalExcludingVat->add($total->getTotalExcludingVat());
            $totalVat = $totalVat->add($total->getTotalVat());

            foreach ($total->getVatLines() as $line) {
                $rate = $line->getVatPercentage()->get();
                $lines[$rate] = isset($lines[$rate]) ? $lines[$rate]->add($line) : $line;
            }
        }

        foreach ($negativeTotals as $total) {
            $totalExcludingVat = $totalExcludingVat->subtract($total->getTotalExcludingVat());
            $totalVat = $totalVat->subtract($total->getTotalVat());

            foreach ($total->getVatLines() as $line) {
                $rate = $line->getVatPercentage()->get();
                $lines[$rate] = isset($lines[$rate])
                    ? $lines[$rate]->subtract($line)
                    : new VatAllocatedLine(
                        Cash::zero()->subtract($line->getTaxableBase()),
                        Cash::zero()->subtract($line->getVatAmount()),
                        $line->getVatPercentage(),
                    );
            }
        }

        uksort($lines, fn (string|int $left, string|int $right): int => bccomp((string) $right, (string) $left, 6));

        return new VatAllocatedTotalPrice(
            array_values($lines),
            $totalExcludingVat,
            $totalVat,
            $totalExcludingVat->add($totalVat),
        );
    }
}
