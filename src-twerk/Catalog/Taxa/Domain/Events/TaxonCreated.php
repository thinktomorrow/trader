<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Catalog\Taxa\Domain\Events;

class TaxonCreated
{
    public string $taxonId;

    public function __construct(string $taxonId)
    {
        $this->taxonId = $taxonId;
    }
}
