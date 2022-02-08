<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Catalog\Products\Domain\Events;

final class TaxonQueuedForDeletion
{
    public string $taxonId;

    public function __construct(string $taxonId)
    {
        $this->taxonId = $taxonId;
    }
}
