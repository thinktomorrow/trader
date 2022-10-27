<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models;

use Thinktomorrow\Trader\Application\Common\HasLocale;
use Thinktomorrow\Trader\Application\Common\RendersData;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonNode;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonState;
use Thinktomorrow\Vine\DefaultNode;

class DefaultTaxonNode extends DefaultNode implements TaxonNode
{
    use HasLocale;
    use RendersData;

    public readonly string $id;

    /** @var array */
    protected array $keys;

    protected TaxonState $taxonState;
    public readonly int $order; // Make publicly available for sorting via vine
    protected array $data;
    protected array $product_ids;
    protected ?string $parentId;
    protected iterable $images;

    private function __construct(string $id, TaxonState $taxonState, int $order, array $data, array $product_ids, array $keys, ?string $parentId = null)
    {
        $this->id = $id;
        $this->taxonState = $taxonState;
        $this->order = $order;
        $this->data = $data;
        $this->product_ids = $product_ids;
        $this->keys = $keys;
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
            TaxonState::from($state['state']),
            $state['order'],
            $state['data'] ? json_decode($state['data'], true) : [],
            $state['product_ids'] ? explode(',', $state['product_ids']) : [],
            json_decode($state['keys'], true),
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

    public function getKey(): ?string
    {
        if (count($this->keys) < 1) {
            return null;
        }

        $localeString = $this->getLocale()->get();

        foreach ($this->keys as $key) {
            if ($key['locale'] == $localeString) {
                return $key['key'];
            }
        }

        if (! isset($this->keys[0])) {
            dd($this->keys);
        }

        return $this->keys[0]['key'];
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
        return $this->getKey();
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

        if (! $this->isRootNode()) {
            $label = array_reduce(array_reverse($this->getBreadCrumbs()), function ($carry, $taxon) use ($withoutRoot) {
                if ($taxon->isRootNode()) {
                    return $withoutRoot ? $carry : $taxon->getLabel() . ': ' . $carry;
                }

                return $taxon->getLabel() . ' > ' . $carry;
            }, $this->getLabel());
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
