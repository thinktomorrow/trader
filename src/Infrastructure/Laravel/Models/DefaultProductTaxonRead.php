<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models;

use Thinktomorrow\Trader\Application\Common\HasLocale;
use Thinktomorrow\Trader\Application\Common\RendersData;
use Thinktomorrow\Trader\Application\Product\ProductTaxa\ProductTaxonRead;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonState;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyType;

class DefaultProductTaxonRead implements ProductTaxonRead
{
    use HasLocale;
    use RendersData;

    protected string $productId;

    protected readonly string $taxonId;
    protected readonly string $taxonomyId;
    protected readonly TaxonomyType $taxonomyType;
    protected readonly bool $showsInGrid;

    protected TaxonState $taxonState;
    protected readonly int $order; // Make publicly available for sorting via vine
    protected array $data;

    private function __construct(string $productId, string $taxonId, string $taxonomyId, TaxonomyType $taxonomyType, bool $showsInGrid, TaxonState $taxonState, int $order, array $data)
    {
        $this->productId = $productId;
        $this->taxonId = $taxonId;
        $this->taxonomyId = $taxonomyId;
        $this->taxonomyType = $taxonomyType;
        $this->showsInGrid = $showsInGrid;

        $this->taxonState = $taxonState;
        $this->order = $order;
        $this->data = $data;
    }

    public static function fromMappedData(array $state): static
    {
        if (!isset($state['taxonomy_type'])) {
            dd($state);
        }

        return new static(
            $state['product_id'],
            $state['taxon_id'],
            $state['taxonomy_id'],
            TaxonomyType::from($state['taxonomy_type']),
            (bool)$state['shows_in_grid'],
            TaxonState::from($state['state']),
            $state['order'],
            $state['data'] ? json_decode($state['data'], true) : [],
        );
    }

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function getTaxonId(): string
    {
        return $this->taxonId;
    }

    public function getTaxonomyId(): string
    {
        return $this->taxonomyId;
    }

    public function getTaxonomyType(): string
    {
        return $this->taxonomyType->value;
    }

    public function getLabel(?string $locale = null): string
    {
        return $this->dataAsPrimitive('title', $locale, '');
    }

    public function showOnline(): bool
    {
        return in_array($this->taxonState, TaxonState::onlineStates());
    }

    public function showsInGrid(): bool
    {
        return $this->showsInGrid;
    }

    public function getOrder(): int
    {
        return $this->order;
    }
}
