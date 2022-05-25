<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models;

use Thinktomorrow\Vine\DefaultNode;
use Thinktomorrow\Trader\Application\Common\HasLocale;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonState;
use Thinktomorrow\Trader\Application\Common\RendersData;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonNode;

class DefaultTaxonNode extends DefaultNode implements TaxonNode
{
    use HasLocale;
    use RendersData;

    public readonly string $id;
    private string $key;
    private TaxonState $taxonState;
    public readonly int $order; // Make publicly available for sorting via vine
    private array $data;
    private array $product_ids;
    private ?string $parentId;

    private function __construct(string $id, string $key, TaxonState $taxonState, int $order, array $data, array $product_ids, ?string $parentId = null)
    {
        $this->id = $id;
        $this->key = $key;
        $this->taxonState = $taxonState;
        $this->order = $order;
        $this->data = $data;
        $this->product_ids = $product_ids;
        $this->parentId = $parentId;

        // Add node entry data so we can use it for sorting.
        parent::__construct([
            'id' => $id,
            'parent_id' => $parentId,
            'order' => $order,
        ]);
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

    public function getKey(): string
    {
        return $this->key;
    }

    public function getLabel(): string
    {
        return $this->data('title', null, $this->getKey());
    }

    public function getContent(): ?string
    {
        return $this->data('content');
    }

    public function showOnline(): bool
    {
        return in_array($this->taxonState, TaxonState::onlineStates());
    }

    public function getProductIds(): array
    {
        return $this->product_ids;
    }

    public function getUrl(): string
    {
        $slug = array_reduce($this->getBreadCrumbs(), function ($carry, $node) {
            return $carry .'/'. $node->getKey();
        }, '');

        $slug .= '/' . $this->getKey();

        // TODO: route should be in project... as chief route resolver of pages.
        return route('taxons.show', trim($slug, '/'));
    }

    public function getBreadCrumbs(): array
    {
        return $this->getAncestorNodes()->all();
    }

    public function getBreadCrumbLabelWithoutRoot(): string
    {
        return $this->getBreadcrumbLabel(true);
    }

    public function getBreadCrumbLabel(bool $withoutRoot = false): string
    {
        $label = $this->getLabel();

        if ($this->isLeafNode()) {
            $label = array_reduce($this->getBreadCrumbs(), function ($carry, $taxon) use ($withoutRoot) {
                if ($taxon->isRootNode()) {
                    return $withoutRoot ? $carry : $taxon->getLabel() . ': ' . $carry;
                }

                return $taxon->getLabel() . ' > ' . $carry;
            }, $label);
        }

        return $label;
    }

    public function getImages(): array
    {
        return $this->data('images', null, []);
    }
}
