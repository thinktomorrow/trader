<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models;

use Thinktomorrow\Trader\Application\Common\HasLocale;
use Thinktomorrow\Trader\Application\Common\RendersData;
use Thinktomorrow\Trader\Application\Taxonomy\TaxonomyItem;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonState;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyState;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyType;

class DefaultTaxonomyItem implements TaxonomyItem
{
    use HasLocale;
    use RendersData;

    public string $taxonomyId;
    protected TaxonState $taxonState;
    protected TaxonomyType $type;
    protected int $order;
    protected array $data;

    private function __construct(string $taxonomyId, TaxonomyState $state, TaxonomyType $type, int $order, array $data)
    {
        $this->taxonomyId = $taxonomyId;
        $this->state = $state;
        $this->type = $type;
        $this->order = $order;
        $this->data = $data;
    }

    public static function fromMappedData(array $state): static
    {
        return new static(
            $state['taxonomy_id'],
            TaxonomyState::from($state['state']),
            TaxonomyType::from($state['type']),
            $state['order'],
            $state['data'] ? json_decode($state['data'], true) : [],
        );
    }

    public function getTaxonomyId(): string
    {
        return $this->taxonomyId;
    }

    public function getTaxonomyType(): string
    {
        return $this->type->value;
    }

    public function getLabel(?string $locale = null): string
    {
        return $this->dataAsPrimitive('title', $locale, '');
    }

    public function showOnline(): bool
    {
        return in_array($this->state, TaxonomyState::onlineStates());
    }
}
