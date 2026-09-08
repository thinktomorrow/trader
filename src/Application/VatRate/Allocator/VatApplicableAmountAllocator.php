<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\VatRate\Allocator;

use Money\Currency;
use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultItemPrice;
use Thinktomorrow\Trader\Domain\Common\Price\ItemPrice;
use Thinktomorrow\Trader\Domain\Common\Price\TaxMode;
use Thinktomorrow\Trader\Domain\Common\Price\VatApplicableAmount;
use Thinktomorrow\Trader\Domain\Common\Vat\VatAllocatedLine;
use Thinktomorrow\Trader\Domain\Common\Vat\VatAllocatedTotalPrice;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;

final class VatApplicableAmountAllocator
{
    public function __construct(private ProRateAllocator $proRateAllocator) {}

    /**
     * @param  array<string, ItemPrice>  $itemTotalsPerRate
     */
    public function allocate(array $itemTotalsPerRate, VatApplicableAmount $amount): VatAllocatedTotalPrice
    {
        $currency = $amount->getAmount()->getCurrency();

        if ($itemTotalsPerRate === []) {
            $zero = new Money('0', $currency);
            $line = new VatAllocatedLine($amount->getAmount(), $zero, VatPercentage::zero());

            return new VatAllocatedTotalPrice([$line], $amount->getAmount(), $zero, $amount->getAmount());
        }

        $itemTotalsPerRate = $this->normalizeZeroItemBases($itemTotalsPerRate, $currency);

        $excludingVat = $amount->getTaxMode() === TaxMode::Exclusive
            ? $amount->getAmount()
            : $this->deriveExcludingVatFromIncludingVat($itemTotalsPerRate, $amount->getAmount());

        return $this->allocateResolved($itemTotalsPerRate, $amount, $excludingVat);
    }

    /**
     * @param  array<string, ItemPrice>  $itemBasesPerVatRate
     */
    public function allocateResolved(array $itemBasesPerVatRate, VatApplicableAmount $amount, Money $resolvedExcludingVat): VatAllocatedTotalPrice
    {
        if ($itemBasesPerVatRate === []) {
            $zero = new Money('0', $amount->getAmount()->getCurrency());
            $line = new VatAllocatedLine($resolvedExcludingVat, $amount->getAmount()->subtract($resolvedExcludingVat), VatPercentage::zero());

            return new VatAllocatedTotalPrice([$line], $resolvedExcludingVat, $line->getVatAmount(), $amount->getAmount());
        }

        $itemBasesPerVatRate = $this->normalizeZeroItemBases($itemBasesPerVatRate, $amount->getAmount()->getCurrency());

        $allocatedBases = $this->proRateAllocator->allocate($itemBasesPerVatRate, $resolvedExcludingVat);
        $vatLines = $this->buildVatLines($allocatedBases);

        if ($amount->getTaxMode() === TaxMode::Inclusive) {
            $vatLines = $this->reconcileIncludingVat($vatLines, $amount->getAmount());
        }

        return $this->buildTotal($vatLines);
    }

    /**
     * @param  array<string, ItemPrice>  $itemTotalsPerRate
     */
    public function deriveExcludingVatFromIncludingVat(array $itemTotalsPerRate, Money $includingVat): Money
    {
        if ($itemTotalsPerRate === []) {
            return $includingVat;
        }

        $itemTotalsPerRate = $this->normalizeZeroItemBases($itemTotalsPerRate, $includingVat->getCurrency());
        $sumOfBases = $this->sumItemBases($itemTotalsPerRate);
        $rateScale = $this->maximumRateScale($itemTotalsPerRate);
        $rateFactor = bcpow('10', (string) $rateScale, 0);
        $weightedGrossBase = '0';

        foreach ($itemTotalsPerRate as $itemPrice) {
            $grossFactor = bcadd(
                bcmul('100', $rateFactor, 0),
                $this->decimalToScaledInteger($itemPrice->getVatPercentage()->get(), $rateScale),
                0,
            );
            $weightedGrossBase = bcadd(
                $weightedGrossBase,
                bcmul($itemPrice->getExcludingVat()->getAmount(), $grossFactor, 0),
                0,
            );
        }

        $numerator = bcmul(
            bcmul($includingVat->getAmount(), $sumOfBases->getAmount(), 0),
            bcmul('100', $rateFactor, 0),
            0,
        );

        return new Money(
            $this->roundPositiveFractionHalfUp($numerator, $weightedGrossBase),
            $includingVat->getCurrency(),
        );
    }

    /**
     * @param  array<string, Money>  $allocatedBases
     * @return VatAllocatedLine[]
     */
    private function buildVatLines(array $allocatedBases): array
    {
        $vatLines = [];
        uksort($allocatedBases, fn (string|int $left, string|int $right): int => bccomp((string) $right, (string) $left, 6));

        foreach ($allocatedBases as $rate => $base) {
            $vatPercentage = VatPercentage::fromString((string) $rate);
            $rateScale = $this->decimalScale($vatPercentage->get());
            $rateFactor = bcpow('10', (string) $rateScale, 0);
            $vatAmount = new Money(
                $this->roundPositiveFractionHalfUp(
                    bcmul($base->getAmount(), $this->decimalToScaledInteger($vatPercentage->get(), $rateScale), 0),
                    bcmul('100', $rateFactor, 0),
                ),
                $base->getCurrency(),
            );

            $vatLines[] = new VatAllocatedLine($base, $vatAmount, $vatPercentage);
        }

        return $vatLines;
    }

    /**
     * @param  VatAllocatedLine[]  $vatLines
     * @return VatAllocatedLine[]
     */
    private function reconcileIncludingVat(array $vatLines, Money $authoritativeIncludingVat): array
    {
        $currentIncludingVat = $this->sumLinesIncludingVat($vatLines, $authoritativeIncludingVat);
        $delta = (int) $authoritativeIncludingVat->subtract($currentIncludingVat)->getAmount();

        if ($delta === 0) {
            return $vatLines;
        }

        if (abs($delta) > count($vatLines)) {
            throw new \LogicException('VAT reconciliation difference exceeds one minor unit per VAT rate: '.$delta.'.');
        }

        usort($vatLines, function (VatAllocatedLine $left, VatAllocatedLine $right) use ($delta): int {
            $leftResidual = $this->vatRoundingResidual($left);
            $rightResidual = $this->vatRoundingResidual($right);
            $comparison = bccomp($rightResidual, $leftResidual, 12);

            if ($delta < 0) {
                $comparison *= -1;
            }

            return $comparison !== 0
                ? $comparison
                : bccomp($right->getVatPercentage()->get(), $left->getVatPercentage()->get(), 6);
        });

        $adjustment = $delta > 0 ? 1 : -1;

        for ($index = 0; $index < abs($delta); $index++) {
            $line = $vatLines[$index];
            $vatLines[$index] = new VatAllocatedLine(
                $line->getTaxableBase(),
                $line->getVatAmount()->add(new Money((string) $adjustment, $line->getVatAmount()->getCurrency())),
                $line->getVatPercentage(),
            );
        }

        return $vatLines;
    }

    private function vatRoundingResidual(VatAllocatedLine $line): string
    {
        $exactVat = bcdiv(
            bcmul($line->getTaxableBase()->getAmount(), $line->getVatPercentage()->get(), 12),
            '100',
            12,
        );

        return bcsub($exactVat, $line->getVatAmount()->getAmount(), 12);
    }

    /**
     * @param  VatAllocatedLine[]  $vatLines
     */
    private function buildTotal(array $vatLines): VatAllocatedTotalPrice
    {
        $firstLine = reset($vatLines);
        $currency = $firstLine->getTaxableBase()->getCurrency();
        $totalExcludingVat = new Money('0', $currency);
        $totalVat = new Money('0', $currency);

        foreach ($vatLines as $vatLine) {
            $totalExcludingVat = $totalExcludingVat->add($vatLine->getTaxableBase());
            $totalVat = $totalVat->add($vatLine->getVatAmount());
        }

        return new VatAllocatedTotalPrice(
            $vatLines,
            $totalExcludingVat,
            $totalVat,
            $totalExcludingVat->add($totalVat),
        );
    }

    /**
     * @param  array<string, ItemPrice>  $itemTotalsPerRate
     */
    private function sumItemBases(array $itemTotalsPerRate): Money
    {
        $firstPrice = reset($itemTotalsPerRate);

        if (! $firstPrice instanceof ItemPrice) {
            throw new \InvalidArgumentException('itemTotalsPerRate must contain ItemPrice instances.');
        }

        $sum = new Money('0', $firstPrice->getExcludingVat()->getCurrency());

        foreach ($itemTotalsPerRate as $itemPrice) {
            if (! $itemPrice instanceof ItemPrice) {
                throw new \InvalidArgumentException('itemTotalsPerRate must contain ItemPrice instances.');
            }

            $sum = $sum->add($itemPrice->getExcludingVat());
        }

        return $sum;
    }

    /**
     * @param  VatAllocatedLine[]  $vatLines
     */
    private function sumLinesIncludingVat(array $vatLines, Money $reference): Money
    {
        $sum = new Money('0', $reference->getCurrency());

        foreach ($vatLines as $vatLine) {
            $sum = $sum->add($vatLine->getTotalIncludingVat());
        }

        return $sum;
    }

    /**
     * @param  array<string, ItemPrice>  $itemTotalsPerRate
     * @return array<string, ItemPrice>
     */
    private function normalizeZeroItemBases(array $itemTotalsPerRate, Currency $currency): array
    {
        if (! $this->sumItemBases($itemTotalsPerRate)->isZero()) {
            return $itemTotalsPerRate;
        }

        uksort($itemTotalsPerRate, fn (string|int $left, string|int $right): int => bccomp((string) $right, (string) $left, 6));
        $firstRate = array_key_first($itemTotalsPerRate);
        $normalized = [];

        foreach ($itemTotalsPerRate as $rate => $itemPrice) {
            $normalized[$rate] = DefaultItemPrice::fromExcludingVat(
                new Money($rate === $firstRate ? '1' : '0', $currency),
                $itemPrice->getVatPercentage(),
            );
        }

        return $normalized;
    }

    /** @param array<string, ItemPrice> $itemTotalsPerRate */
    private function maximumRateScale(array $itemTotalsPerRate): int
    {
        $scale = 0;

        foreach ($itemTotalsPerRate as $itemPrice) {
            $scale = max($scale, $this->decimalScale($itemPrice->getVatPercentage()->get()));
        }

        return $scale;
    }

    private function decimalScale(string $decimal): int
    {
        return str_contains($decimal, '.') ? strlen(rtrim(substr(strrchr($decimal, '.'), 1), '0')) : 0;
    }

    private function decimalToScaledInteger(string $decimal, int $scale): string
    {
        [$whole, $fraction] = array_pad(explode('.', $decimal, 2), 2, '');

        if ($scale === 0) {
            return $whole;
        }

        $factor = bcpow('10', (string) $scale, 0);

        return bcadd(
            bcmul($whole, $factor, 0),
            str_pad(substr($fraction, 0, $scale), $scale, '0'),
            0,
        );
    }

    private function roundPositiveFractionHalfUp(string $numerator, string $denominator): string
    {
        $quotient = bcdiv($numerator, $denominator, 0);
        $remainder = bcsub($numerator, bcmul($quotient, $denominator, 0), 0);

        return bccomp(bcmul($remainder, '2', 0), $denominator, 0) >= 0
            ? bcadd($quotient, '1', 0)
            : $quotient;
    }
}
