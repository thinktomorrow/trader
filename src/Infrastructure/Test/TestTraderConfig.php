<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Test;

use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\TraderConfig;

class TestTraderConfig implements TraderConfig
{
    private array $overwrites;

    public function __construct(array $overwrites = [])
    {
        $this->overwrites = $overwrites;
    }

    public function getDefaultLocale(): Locale
    {
        return Locale::fromString('nl', 'BE');
    }

    public function getDefaultCurrency(): string
    {
        return 'EUR';
    }

    public function getDefaultTaxRate(): string
    {
        return '10';
    }

    public function getAvailableTaxRates(): array
    {
        return ['21', '12', '6', '10'];
    }

    public function doesPriceInputIncludesVat(): bool
    {
        return true;
    }

    public function getCategoryRootId(): ?string
    {
        return $this->overwrites['category_root_id'] ?? null;
    }

    public function getClassMap(): array
    {
        return [];
    }
}
