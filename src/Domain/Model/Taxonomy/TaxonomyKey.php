<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Taxonomy;

use Thinktomorrow\Trader\Domain\Common\Entity\ChildEntity;
use Thinktomorrow\Trader\Domain\Common\Locale;

class TaxonomyKey implements ChildEntity
{
    public readonly TaxonomyId $taxonomyId;
    public readonly TaxonomyKeyId $taxonomyKeyId;
    private Locale $locale;

    private function __construct()
    {
    }

    public static function create(TaxonomyId $taxonomyId, TaxonomyKeyId $key, Locale $locale): static
    {
        $taxonomyKey = new static();

        $taxonomyKey->taxonomyId = $taxonomyId;
        $taxonomyKey->taxonomyKeyId = $key;
        $taxonomyKey->locale = $locale;

        return $taxonomyKey;
    }

    public function getLocale(): Locale
    {
        return $this->locale;
    }

    public function getMappedData(): array
    {
        return [
            'taxonomy_id' => $this->taxonomyId->get(),
            'key' => $this->taxonomyKeyId->get(),
            'locale' => $this->locale->get(),
        ];
    }

    public static function fromMappedData(array $state, array $aggregateState): static
    {
        $taxonomyKey = new static();

        $taxonomyKey->taxonomyId = TaxonomyId::fromString($aggregateState['taxonomy_id']);
        $taxonomyKey->taxonomyKeyId = TaxonomyKeyId::fromString($state['key']);
        $taxonomyKey->locale = Locale::fromString($state['locale']);

        return $taxonomyKey;
    }
}
