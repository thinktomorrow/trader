<?php

namespace Thinktomorrow\Trader\Tax\Domain;

interface Taxable
{
    public function taxId(): TaxId;
}