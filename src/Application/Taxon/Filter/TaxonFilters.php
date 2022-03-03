<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Taxon\Filter;

use Assert\Assertion;
use Thinktomorrow\Trader\Application\Common\ArrayCollection;

class TaxonFilters extends ArrayCollection
{
    public static function fromType(array $items): static
    {
        Assertion::allIsInstanceOf($items, TaxonFilter::class);

        return new static($items);
    }
}
