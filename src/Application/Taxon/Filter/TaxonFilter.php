<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Taxon\Filter;

use Thinktomorrow\Vine\NodeSource;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonState;
use Thinktomorrow\Trader\Application\Common\RendersData;

class TaxonFilter implements NodeSource
{
    use RendersData;

    public readonly string $id;
    public readonly int $order; // Make publicly available for sorting via vine
    private string $key;
    private TaxonState $taxonState;
    private array $data;
    private array $productIds;
    private ?string $parentId = null;
    private array $children = [];

    private function __construct(string $id, string $key, TaxonState $taxonState, int $order, array $data, array $productIds, ?string $parentId = null)
    {
        $this->id = $id;
        $this->key = $key;
        $this->taxonState = $taxonState;
        $this->order = $order;
        $this->data = $data;
        $this->productIds = $productIds;
        $this->parentId = $parentId;
    }

    public static function fromMappedData(array $state): static
    {
        return new static(
            $state['taxon_id'],
            $state['key'],
            TaxonState::from($state['state']),
            $state['order'],
            $state['data'] ? json_decode($state['data'], true) : [],
            $state['product_ids'] ? explode(',',$state['product_ids']) : [],
            $state['parent_id'],
        );
    }

    public function setChildren(array $children): void
    {
        $this->children = $children;
    }

    public function children(): array
    {
        return $this->children;
    }

    public function hasChildren(): bool
    {
        return count($this->children) > 0;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getLabel(string $language = null): string
    {
        return $this->data('label', $language);
    }

    public function showOnline(): bool
    {
        return in_array($this->taxonState, TaxonState::onlineStates());
    }

    public function getNodeId(): string
    {
        return $this->id;
    }

    public function getParentNodeId(): ?string
    {
        return $this->parentId;
    }

    public function getProductIds(): array
    {
        return $this->productIds;
    }

    public function getChildren(): array
    {
        return $this->children;
    }
}
