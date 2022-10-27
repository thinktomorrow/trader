<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Taxon;

use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonKey;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonKeyId;

class UpdateTaxonKeys
{
    private string $taxonId;
    private array $taxonKeys;

    public function __construct(string $taxonId, array $taxonKeys)
    {
        $this->taxonId = $taxonId;
        $this->taxonKeys = $taxonKeys;
    }

    public function getTaxonId(): TaxonId
    {
        return TaxonId::fromString($this->taxonId);
    }

    /**
     * @return TaxonKey[]
     */
    public function getTaxonKeys(): array
    {
        $result = [];

        foreach ($this->taxonKeys as $locale => $taxonKey) {
            $result[] = TaxonKey::create($this->getTaxonId(), TaxonKeyId::fromString($taxonKey), Locale::fromString($locale));
        }

        return $result;
    }
}
