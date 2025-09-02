<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Taxon;

use Thinktomorrow\Trader\Domain\Common\Entity\ChildEntity;
use Thinktomorrow\Trader\Domain\Common\Locale;

class TaxonKey implements ChildEntity
{
    public readonly TaxonId $taxonId;
    public readonly TaxonKeyId $taxonKeyId;
    private Locale $locale;

    private function __construct()
    {
    }

    public static function create(TaxonId $taxonId, TaxonKeyId $key, Locale $locale): static
    {
        $taxonKey = new static();

        $taxonKey->taxonId = $taxonId;
        $taxonKey->taxonKeyId = $key;
        $taxonKey->locale = $locale;

        return $taxonKey;
    }

    public static function temp(TaxonKeyId $key, Locale $locale): static
    {
        $taxonKey = new static();

        $taxonKey->taxonKeyId = $key;
        $taxonKey->locale = $locale;

        return $taxonKey;
    }

    public function getLocale(): Locale
    {
        return $this->locale;
    }

    public function getMappedData(): array
    {
        return [
            'taxon_id' => $this->taxonId->get(),
            'key' => $this->taxonKeyId->get(),
            'locale' => $this->locale->get(),
        ];
    }

    public static function fromMappedData(array $state, array $aggregateState): static
    {
        $taxonKey = new static();

        $taxonKey->taxonId = TaxonId::fromString($aggregateState['taxon_id']);
        $taxonKey->taxonKeyId = TaxonKeyId::fromString($state['key']);
        $taxonKey->locale = Locale::fromString($state['locale']);

        return $taxonKey;
    }
}
