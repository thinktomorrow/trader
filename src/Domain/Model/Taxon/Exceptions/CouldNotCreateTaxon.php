<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Taxon\Exceptions;

class CouldNotCreateTaxon extends \RuntimeException
{
    public static function becauseParentDoesNotBelongToSameTaxonomy(
        string $parentTaxonId,
        string $taxonomyId,
    ): self {
        return new self(sprintf(
            'Could not create taxon because parent taxon "%s" does not belong to the same taxonomy "%s".',
            $parentTaxonId,
            $taxonomyId
        ));
    }
}
