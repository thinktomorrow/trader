<?php

namespace Thinktomorrow\Trader\Catalog\Products\Domain;

use Illuminate\Support\Collection;
use Money\Money;
use Thinktomorrow\Trader\Catalog\Options\Domain\Options;
use Thinktomorrow\Trader\Taxes\Taxable;

interface Product extends Taxable
{
    public function getId(): string;

    public function getProductGroupId(): string;

    public function isAvailable(): bool;

    public function getUrl(): string;

    public function getOptions(): Options;

    public function hasOption(string $optionId): bool;

    public function getData(): array;

    public function getTotal(): Money;

    public function getDiscountTotal(): Money;

    public function hasDiscount(): bool;

    public function getUnitPrice(): Money;

    public function getTaxTotal(): Money;

    public function isTaxApplicable(): bool;

    public function doPricesIncludeTax(): bool;

    public function getTitle(): string;

    public function getImages(): Collection;
}
