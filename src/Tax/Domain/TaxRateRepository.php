<?php

namespace Thinktomorrow\Trader\Tax\Domain;

interface TaxRateRepository
{
    public function find(TaxId $taxId): TaxRate;

    public function add(TaxRate $taxRate);
}
