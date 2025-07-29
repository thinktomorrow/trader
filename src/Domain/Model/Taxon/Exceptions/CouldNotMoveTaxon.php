<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Taxon\Exceptions;

class CouldNotMoveTaxon extends \RuntimeException
{
    public static function becauseTargetDoesNotBelongToSameTaxonomy(
        string $parentTaxonId,
        string $taxonomyId,
    ): self {
        return new self(sprintf(
            'Could not move taxon because target taxon "%s" does not belong to the same taxonomy "%s"',
            $parentTaxonId,
            $taxonomyId
        ));
    }
}
