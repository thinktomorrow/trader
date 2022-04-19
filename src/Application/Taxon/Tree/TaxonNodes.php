<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Taxon\Tree;

use Assert\Assertion;
use Thinktomorrow\Trader\Application\Common\ArrayCollection;

class TaxonNodes extends ArrayCollection
{
    public static function fromType(array $items): static
    {
        Assertion::allIsInstanceOf($items, TaxonNode::class);

        return new static($items);
    }
}
