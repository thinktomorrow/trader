<?php

namespace Thinktomorrow\Trader\Domain\Model\Order;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Vat\VatAllocatedLine;
use Thinktomorrow\Trader\Domain\Model\Order\Exceptions\VatSnapshotMismatchException;

final class OrderVatSnapshot
{
    /** @var VatAllocatedLine[] */
    private array $vatLines;

    private Money $shippingIncl;
    private Money $paymentIncl;
    private Money $discountIncl;
    private Money $totalVat;
    private Money $totalIncl;

    private function __construct(
        array $vatLines,
        Money $shippingIncl,
        Money $paymentIncl,
        Money $discountIncl,
        Money $totalVat,
        Money $totalIncl,
    ) {
        $this->vatLines = $vatLines;
        $this->shippingIncl = $shippingIncl;
        $this->paymentIncl = $paymentIncl;
        $this->discountIncl = $discountIncl;
        $this->totalVat = $totalVat;
        $this->totalIncl = $totalIncl;
    }

    public static function empty(): self
    {
        return new self(
            [],
            Money::EUR(0),
            Money::EUR(0),
            Money::EUR(0),
            Money::EUR(0),
            Money::EUR(0),
        );
    }

    /**
     * Factory used by application layer (VatAllocator)
     *
     * @param VatAllocatedLine[] $vatLines
     */
    public static function fromVatAllocation(
        array $vatLines,
        Money $shippingIncl,
        Money $paymentIncl,
        Money $discountIncl,
        Money $totalVat,
        Money $totalIncl,
        Money $totalExcl
    ): self {

        // Invariant 1: total_excl + total_vat === total_incl
        if (! $totalExcl->add($totalVat)->equals($totalIncl)) {
            throw new \LogicException(
                'OrderVatSnapshot invariant violated: total_excl + total_vat must equal total_incl: [' .
                $totalExcl->getAmount() . '] + [' . $totalVat->getAmount() . '] != [' . $totalIncl->getAmount() . ']'
            );
        }

        // Invariant 2: sum(vatLines.vat_amount) === totalVat
        $vatSum = Money::EUR(0);

        foreach ($vatLines as $vatLine) {
            if (! $vatLine instanceof VatAllocatedLine) {
                throw new \InvalidArgumentException('vatLines must be instances of VatAllocatedLine.');
            }

            $vatSum = $vatSum->add($vatLine->getVatAmount());
        }

        if (! $vatSum->equals($totalVat)) {
            throw new \LogicException(
                'OrderVatSnapshot invariant violated: sum of vat lines [' .
                $vatSum->getAmount() . '] does not equal total vat [' . $totalVat->getAmount() . '].'
            );
        }

        return new self(
            $vatLines,
            $shippingIncl,
            $paymentIncl,
            $discountIncl,
            $totalVat,
            $totalIncl,
        );
    }

    public static function fromState(array $vatLines, Money $shippingIncl, Money $paymentIncl, Money $discountIncl, Money $totalVat, Money $totalIncl): self
    {
        return new self(
            $vatLines,
            $shippingIncl,
            $paymentIncl,
            $discountIncl,
            $totalVat,
            $totalIncl
        );
    }

    /** @return VatAllocatedLine[] */
    public function getVatLines(): array
    {
        return $this->vatLines;
    }

    public function getShippingIncl(): Money
    {
        return $this->shippingIncl;
    }

    public function getPaymentIncl(): Money
    {
        return $this->paymentIncl;
    }

    public function getDiscountIncl(): Money
    {
        return $this->discountIncl;
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
        if (! $totalExcl->add($this->totalVat)->equals($this->totalIncl)) {
            throw new VatSnapshotMismatchException(
                'Stored VAT snapshot total incl [' . $this->totalIncl->getAmount() .
                '] does not match current order totals excl [' .
                $totalExcl->getAmount() . '] + vat [' .
                $this->totalVat->getAmount() . '].'
            );
        }
    }
}
