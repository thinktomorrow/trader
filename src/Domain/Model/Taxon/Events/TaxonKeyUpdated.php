<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Taxon\Events;

use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonKeyId;

class TaxonKeyUpdated
{
    public function __construct(
        public readonly TaxonId $taxonId,
        public readonly Locale $locale,
        public readonly TaxonKeyId $formerTaxonKeyId,
        public readonly TaxonKeyId $newTaxonKeyId,
    ) {
    }
}
