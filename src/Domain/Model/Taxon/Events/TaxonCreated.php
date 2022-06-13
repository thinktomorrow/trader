<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Taxon\Events;

use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;

class TaxonCreated
{
    public function __construct(public readonly TaxonId $taxonId)
    {
    }
}
