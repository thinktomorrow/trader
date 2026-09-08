<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Price;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Price\Exceptions\PriceCannotBeNegative;

final readonly class VatApplicableAmount
{
    private function __construct(
        private Money $amount,
        private TaxMode $taxMode,
    ) {
        if ($amount->isNegative()) {
            throw new PriceCannotBeNegative('VAT applicable amount cannot be negative: '.$amount->getAmount().' is given.');
        }
    }

    public static function excludingVat(Money $amount): self
    {
        return new self($amount, TaxMode::Exclusive);
    }

    public static function includingVat(Money $amount): self
    {
        return new self($amount, TaxMode::Inclusive);
    }

    public static function fromMoney(Money $amount, TaxMode $taxMode): self
    {
        return new self($amount, $taxMode);
    }

    public function getAmount(): Money
    {
        return $this->amount;
    }

    public function getTaxMode(): TaxMode
    {
        return $this->taxMode;
    }
}
