<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Taxonomy\Events;

use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyId;

class TaxonomyCreated
{
    public function __construct(public readonly TaxonomyId $taxonomyId)
    {
    }
}
