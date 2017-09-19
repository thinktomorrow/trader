<?php

namespace Thinktomorrow\Trader\Tests\Unit;

use Thinktomorrow\Trader\Tax\Domain\TaxId;
use Thinktomorrow\Trader\Tax\Domain\TaxRate;
use Thinktomorrow\Trader\Tax\Domain\TaxRateRepository;

class InMemoryTaxRateRepository implements TaxRateRepository
{
    private static $collection = [];

    public function find(TaxId $taxId): TaxRate
    {
        if (isset(self::$collection[(string) $taxId])) {
            return self::$collection[(string) $taxId];
        }

        throw new \RuntimeException('TaxRate not found by id ['.$taxId->get().']');
    }

    public function add(TaxRate $taxRate)
    {
        self::$collection[(string) $taxRate->id()] = $taxRate;
    }
}
