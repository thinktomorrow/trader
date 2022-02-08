<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Catalog\Taxa\Ports;

use App\ShopAdmin\Catalog\Taxa\TaxonState;
use Illuminate\Support\Collection;
use Thinktomorrow\Trader\Catalog\Taxa\Domain\Taxon;
use Thinktomorrow\Trader\Common\Domain\HasDataAttribute;
use Thinktomorrow\Vine\DefaultNode;

class TaxonNode extends DefaultNode implements Taxon
{
    use HasDataAttribute;

    private string $id;
    private string $key;
    private string $label;
    private ?Taxon $parent;
    private array $data;

    public function __construct(string $id, string $key, string $label, ?Taxon $parent, array $data)
    {
        $this->id = $id;
        $this->key = $key;
        $this->label = $label;
        $this->parent = $parent;
        $this->data = $data;

        // Add the data[order_column] to the node entry so we can use it for sorting.
        parent::__construct($data);
    }

    public function getNodeId($key = null, $default = null): string
    {
        return $this->getId();
    }

    public function getParentNodeId(): ?string
    {
        return $this->data('parent_id');
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
        return $this->label;
    }

    public function getParent(): ?Taxon
    {
        return $this->parent ?: $this->parentNode;
    }

    public function showOnline(): bool
    {
        return $this->data('state') === TaxonState::ONLINE;
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

    public function getProductGroupIds(): array
    {
        return $this->data('productgroup_ids', []);
    }

    public function getUrl(): string
    {
        $slug = array_reduce($this->getBreadCrumbs(), function ($carry, $node) {
            return $carry .'/'. $node->getKey();
        }, '');

        $slug .= '/' . $this->getKey();

        return route('taxons.show', trim($slug, '/'));
    }

    public function getImages(): Collection
    {
        return collect($this->data('images', []));
    }
}
