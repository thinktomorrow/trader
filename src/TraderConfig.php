<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader;

use Thinktomorrow\Trader\Domain\Common\Locale;

interface TraderConfig
{
    public function getDefaultLocale(): Locale;

    public function getDefaultCurrency(): string;

    public function getDefaultTaxRate(): string;

    public function getAvailableTaxRates(): array;

    public function doesPriceInputIncludesVat(): bool;

    /**
     * Prices will be calculated including or excluding vat. This makes sure that calculations are correct
     * and don't cause any rounding errors - which could occur when calculating excluding vat and
     * including the vat afterwards. this can be set according to the visitor demands (b2b or b2c).
     *
     * @return bool
     */
    public function includeVatInPrices(): bool;

    public function getCategoryRootId(): ?string;

    public function getClassMap(): array;

    public function getWebmasterEmail(): string;

    public function getWebmasterName(): string;
}
