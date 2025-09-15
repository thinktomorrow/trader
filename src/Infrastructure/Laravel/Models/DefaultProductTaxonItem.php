<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models;

use Thinktomorrow\Trader\Application\Common\HasLocale;
use Thinktomorrow\Trader\Application\Common\RendersData;
use Thinktomorrow\Trader\Application\Product\Taxa\ProductTaxonItem;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonKey;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonState;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyState;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyType;

class DefaultProductTaxonItem implements ProductTaxonItem
{
    use HasLocale;
    use RendersData;

    protected string $productId;

    protected readonly string $taxonId;
    protected readonly string $taxonomyId;
    protected readonly TaxonomyType $taxonomyType;
    protected readonly bool $showsInGrid;
    protected TaxonState $taxonState;

    /** @var array|TaxonKey[] */
    protected array $keys;
    protected array $data;

    public function __construct(string $productId, string $taxonId, string $taxonomyId, TaxonomyType $taxonomyType, bool $showsInGrid, TaxonState $taxonState, array $taxonKeys, array $data)
    {
        $this->productId = $productId;
        $this->taxonId = $taxonId;
        $this->taxonomyId = $taxonomyId;
        $this->taxonomyType = $taxonomyType;
        $this->showsInGrid = $showsInGrid;

        $this->taxonState = $taxonState;
        $this->keys = array_map(fn (TaxonKey $key) => $key, $taxonKeys);
        $this->data = $data;
    }

    public static function fromMappedData(array $state, array $keys): static
    {
        return new static(
            $state['product_id'],
            $state['taxon_id'],
            $state['taxonomy_id'],
            TaxonomyType::from($state['taxonomy_type']),
            (bool)$state['shows_in_grid'],
            self::determineStateFlag($state),
            $keys,
            array_merge(
                ['taxonomy_data' => ($state['taxonomy_data'] ? json_decode($state['taxonomy_data'], true) : [])],
                ['taxon_data' => ($state['taxon_data'] ? json_decode($state['taxon_data'], true) : [])],
                ($state['data'] ? json_decode($state['data'], true) : []),
            )
        );
    }

    private static function determineStateFlag(array $state): TaxonState
    {
        $productTaxonState = TaxonState::from($state['state']);
        $taxonState = TaxonState::from($state['taxon_state']);
        $taxonomyState = TaxonomyState::from($state['taxonomy_state']);
        $state = TaxonState::online;

        if (! in_array($productTaxonState, TaxonState::onlineStates())) {
            $state = $productTaxonState;
        } elseif (! in_array($taxonState, TaxonState::onlineStates())) {
            $state = $taxonState;
        } elseif (! in_array($taxonomyState, TaxonomyState::onlineStates())) {
            $state = TaxonState::offline;
        }

        return $state;
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

    public function getKey(?string $locale = null): ?string
    {
        if (count($this->keys) < 1 || ! isset($this->keys[0])) {
            return null;
        }

        $locale = $locale ?: $this->getLocale()->get();

        foreach ($this->keys as $key) {
            if ($key->getLocale()->get() == $locale) {
                return $key->taxonKeyId->get();
            }
        }

        return $this->keys[0]->taxonKeyId->get();
    }

    public function getUrl(?string $locale = null): string
    {
        return $this->getKey($locale) ?? '';
    }

    public function getLabel(?string $locale = null): string
    {
        return $this->dataAsPrimitive(
            'title',
            $locale,
            $this->dataAsPrimitive('taxon_data.title', $locale, '')
        );
    }

    public function getTaxonomyLabel(?string $locale = null): string
    {
        return $this->dataAsPrimitive(
            'taxonomy_data.title',
            $locale,
            ''
        );
    }

    public function showOnline(): bool
    {
        return in_array($this->taxonState, TaxonState::onlineStates());
    }

    public function showsInGrid(): bool
    {
        return $this->showsInGrid;
    }
}
