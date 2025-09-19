<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models;

use Thinktomorrow\Trader\Application\Common\HasLocale;
use Thinktomorrow\Trader\Application\Common\RendersData;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonNode;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonKey;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonState;
use Thinktomorrow\Vine\DefaultNode;

class DefaultTaxonNode extends DefaultNode implements TaxonNode
{
    use HasLocale;
    use RendersData;

    public readonly string $id;
    public readonly string $taxonomyId;

    /** @var TaxonKey[] */
    protected array $keys;

    protected TaxonState $taxonState;
    public readonly int $order; // Make publicly available for sorting via vine
    protected array $data;
    protected array $product_ids;
    protected array $grid_product_ids;
    protected array $grid_variant_ids;
    protected ?string $parentId;
    protected iterable $images;

    private function __construct(string $id, string $taxonomyId, TaxonState $taxonState, int $order, array $data, array $product_ids, array $grid_product_ids, array $grid_variant_ids, array $keys, ?string $parentId = null)
    {
        $this->id = $id;
        $this->taxonomyId = $taxonomyId;
        $this->taxonState = $taxonState;
        $this->order = $order;
        $this->data = $data;
        $this->product_ids = $product_ids;
        $this->grid_product_ids = $grid_product_ids;
        $this->grid_variant_ids = $grid_variant_ids;
        $this->keys = array_map(fn (TaxonKey $key) => $key, $keys);
        $this->parentId = $parentId;

        // Add node entry data so we can use it for sorting.
        parent::__construct([
            'id' => $id,
            'parent_id' => $parentId,
            'order' => $order,
        ]);
    }

    public static function fromMappedData(array $state, array $taxonKeys): static
    {
        return new static(
            $state['taxon_id'],
            $state['taxonomy_id'],
            TaxonState::from($state['state']),
            $state['order'],
            $state['data'] ? json_decode($state['data'], true) : [],
            $state['product_ids'] ? array_unique(explode(',', $state['product_ids'])) : [],
            $state['grid_product_ids'] ? self::decodeProductVariantPairs($state['grid_product_ids']) : [],
            $state['grid_variant_ids'] ? self::decodeProductVariantPairs($state['grid_variant_ids']) : [],
            $taxonKeys,
            $state['parent_id'],
        );
    }

    private static function decodeProductVariantPairs(string $encodedPairs): array
    {
        $pairs = array_unique(explode(',', $encodedPairs));

        $result = [];

        foreach ($pairs as $pair) {

            if (strpos($pair, ':') === false) {
                throw new \InvalidArgumentException("Invalid product_variant pair: $pair");
            }

            [$productId, $variantId] = explode(':', $pair);
            $result[] = ['product_id' => $productId, 'variant_id' => $variantId];
        }

        return $result;
    }

    public function getNodeId($key = null, $default = null): string
    {
        return $this->getId();
    }

    public function getParentNodeId(): ?string
    {
        return $this->parentId;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTaxonomyId(): string
    {
        return $this->taxonomyId;
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
        return $this->dataAsPrimitive('title', $locale, $this->getKey());
    }

    public function getContent(?string $locale = null): ?string
    {
        return $this->dataAsPrimitive('content', $locale);
    }

    public function showOnline(): bool
    {
        return in_array($this->taxonState, TaxonState::onlineStates());
    }

    public function getProductIds(): array
    {
        return $this->product_ids;
    }

    public function getGridProductIds(): array
    {
        return array_unique(array_merge(
            array_map(fn ($ids) => $ids['product_id'], $this->grid_product_ids),
            array_map(fn ($ids) => $ids['product_id'], $this->grid_variant_ids)
        ));
    }

    //    public function getInclusiveOnlineProductIds(): array
    //    {
    //        return array_map(fn($ids) => $ids['product_id'], $this->grid_product_ids);
    //    }

    public function getGridVariantIds(): array
    {
        return array_unique(array_merge(
            array_map(fn ($ids) => $ids['variant_id'], $this->grid_product_ids),
            array_map(fn ($ids) => $ids['variant_id'], $this->grid_variant_ids)
        ));
    }

    public function hasGridProduct(array $productIds): bool
    {
        return count(array_intersect($this->getGridProductIds(), $productIds)) > 0;
    }

    /**
     * Get the count of products that are both in this taxon
     * and in the given array of product IDs. This allows
     * to show the total number in a filter, for example.
     */
    public function getProductCount(array $productIds): int
    {
        // All products in this taxon including all children and grandchildren etc.
        $allProductIds = array_unique(
            array_merge(
                $this->getGridProductIds(),
                ...$this->pluckChildNodes('getGridProductIds')
            )
        );

        return count(array_intersect($allProductIds, $productIds));
    }

    /**
     * Get the count of products that are in this taxon,
     * not including products in child nodes.
     */
    public function getExclusiveProductCount(array $productIds): int
    {
        return count(array_intersect($this->grid_product_ids, $productIds));
    }

    /**
     * The total number of products in this taxon,
     * regardless of current grid or filtering.
     */
    public function getProductTotal(): int
    {
        return count($this->grid_product_ids);
    }

    public function getBreadCrumbs(): array
    {
        return $this->getAncestorNodes()->all();
    }

    public function getBreadCrumbLabelWithoutRoot(?string $locale = null): string
    {
        return $this->getBreadcrumbLabel($locale, true);
    }

    public function getBreadCrumbLabel(?string $locale = null, bool $withoutRoot = false): string
    {
        $label = $this->getLabel($locale);

        if (! $this->isRootNode()) {
            $label = array_reduce(array_reverse($this->getBreadCrumbs()), function ($carry, $taxon) use ($withoutRoot, $locale) {
                if ($taxon->isRootNode()) {
                    return $withoutRoot ? $carry : $taxon->getLabel($locale) . ': ' . $carry;
                }

                return $taxon->getLabel($locale) . ' > ' . $carry;
            }, $this->getLabel($locale));
        }

        return $label;
    }

    public function setImages(iterable $images): void
    {
        $this->images = $images;
    }

    public function getImages(): iterable
    {
        return $this->images;
    }
}
