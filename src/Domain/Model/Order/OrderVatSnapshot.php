<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Vat\VatAllocatedLine;
use Thinktomorrow\Trader\Domain\Model\Order\Exceptions\VatSnapshotMismatchException;

final class OrderVatSnapshot
{
    /**
     * @param  VatAllocatedLine[]  $vatLines
     */
    private function __construct(
        private array $vatLines,
        private Money $subtotalExcl,
        private Money $subtotalIncl,
        private Money $shippingExcl,
        private Money $shippingIncl,
        private Money $paymentExcl,
        private Money $paymentIncl,
        private Money $discountExcl,
        private Money $discountIncl,
        private Money $totalExcl,
        private Money $totalVat,
        private Money $totalIncl,
        private ?string $pricingFingerprint,
    ) {}

    public static function empty(): self
    {
        $zero = Money::EUR(0);

        return new self([], $zero, $zero, $zero, $zero, $zero, $zero, $zero, $zero, $zero, $zero, $zero, null);
    }

    /**
     * @param  VatAllocatedLine[]  $vatLines
     */
    public static function fromVatAllocation(
        array $vatLines,
        Money $subtotalExcl,
        Money $subtotalIncl,
        Money $shippingExcl,
        Money $shippingIncl,
        Money $paymentExcl,
        Money $paymentIncl,
        Money $discountExcl,
        Money $discountIncl,
        Money $totalExcl,
        Money $totalVat,
        Money $totalIncl,
        ?string $pricingFingerprint = null,
    ): self {
        self::assertTotals($vatLines, $subtotalExcl, $subtotalIncl, $shippingExcl, $shippingIncl, $paymentExcl, $paymentIncl, $discountExcl, $discountIncl, $totalExcl, $totalVat, $totalIncl);

        return new self(
            $vatLines,
            $subtotalExcl,
            $subtotalIncl,
            $shippingExcl,
            $shippingIncl,
            $paymentExcl,
            $paymentIncl,
            $discountExcl,
            $discountIncl,
            $totalExcl,
            $totalVat,
            $totalIncl,
            $pricingFingerprint,
        );
    }

    /**
     * @param  VatAllocatedLine[]  $vatLines
     */
    public static function fromState(
        array $vatLines,
        Money $subtotalExcl,
        Money $subtotalIncl,
        Money $shippingExcl,
        Money $shippingIncl,
        Money $paymentExcl,
        Money $paymentIncl,
        Money $discountExcl,
        Money $discountIncl,
        Money $totalExcl,
        Money $totalVat,
        Money $totalIncl,
        ?string $pricingFingerprint = null,
    ): self {
        return self::fromVatAllocation(
            $vatLines,
            $subtotalExcl,
            $subtotalIncl,
            $shippingExcl,
            $shippingIncl,
            $paymentExcl,
            $paymentIncl,
            $discountExcl,
            $discountIncl,
            $totalExcl,
            $totalVat,
            $totalIncl,
            $pricingFingerprint,
        );
    }

    /** @return VatAllocatedLine[] */
    public function getVatLines(): array
    {
        return $this->vatLines;
    }

    public function getSubtotalExcl(): Money
    {
        return $this->subtotalExcl;
    }

    public function getSubtotalIncl(): Money
    {
        return $this->subtotalIncl;
    }

    public function getShippingExcl(): Money
    {
        return $this->shippingExcl;
    }

    public function getShippingIncl(): Money
    {
        return $this->shippingIncl;
    }

    public function getPaymentExcl(): Money
    {
        return $this->paymentExcl;
    }

    public function getPaymentIncl(): Money
    {
        return $this->paymentIncl;
    }

    public function getDiscountExcl(): Money
    {
        return $this->discountExcl;
    }

    public function getDiscountIncl(): Money
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

    public function assertMatchesTotalExcl(Money $totalExcl): void
    {
        if (! $this->totalExcl->equals($totalExcl)) {
            throw new VatSnapshotMismatchException(
                sprintf(
                    'Stored pricing snapshot total excl [%s] does not match current order total excl [%s].',
                    $this->totalExcl->getAmount(),
                    $totalExcl->getAmount(),
                )
            );
        }
    }

    public function assertMatchesPricingFingerprint(string $pricingFingerprint): void
    {
        if ($this->pricingFingerprint !== null && $this->pricingFingerprint !== $pricingFingerprint) {
            throw new VatSnapshotMismatchException('Stored pricing snapshot no longer matches the current order calculation inputs.');
        }
    }

    public function getPricingFingerprint(): ?string
    {
        return $this->pricingFingerprint;
    }

    /**
     * @param  VatAllocatedLine[]  $vatLines
     */
    private static function assertTotals(
        array $vatLines,
        Money $subtotalExcl,
        Money $subtotalIncl,
        Money $shippingExcl,
        Money $shippingIncl,
        Money $paymentExcl,
        Money $paymentIncl,
        Money $discountExcl,
        Money $discountIncl,
        Money $totalExcl,
        Money $totalVat,
        Money $totalIncl,
    ): void {
        if (! $totalExcl->add($totalVat)->equals($totalIncl)) {
            throw new \LogicException('Pricing snapshot invariant violated: total_excl + total_vat must equal total_incl.');
        }

        if (! $subtotalExcl->add($shippingExcl)->add($paymentExcl)->subtract($discountExcl)->equals($totalExcl)) {
            throw new \LogicException('Pricing snapshot invariant violated: excluding-VAT components do not equal total_excl.');
        }

        if (! $subtotalIncl->add($shippingIncl)->add($paymentIncl)->subtract($discountIncl)->equals($totalIncl)) {
            throw new \LogicException('Pricing snapshot invariant violated: including-VAT components do not equal total_incl.');
        }

        $vatSum = Money::EUR(0);
        $taxableBaseSum = Money::EUR(0);

        foreach ($vatLines as $vatLine) {
            if (! $vatLine instanceof VatAllocatedLine) {
                throw new \InvalidArgumentException('vatLines must be instances of VatAllocatedLine.');
            }

            $vatSum = $vatSum->add($vatLine->getVatAmount());
            $taxableBaseSum = $taxableBaseSum->add($vatLine->getTaxableBase());
        }

        if (! $vatSum->equals($totalVat)) {
            throw new \LogicException('Pricing snapshot invariant violated: VAT lines do not equal total_vat.');
        }

        if (! $taxableBaseSum->equals($totalExcl)) {
            throw new \LogicException('Pricing snapshot invariant violated: VAT taxable bases do not equal total_excl.');
        }
    }
}
