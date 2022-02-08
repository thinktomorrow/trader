<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Catalog\Products\Ports;

use Illuminate\Support\Collection;
use Thinktomorrow\Trader\Catalog\Options\Domain\Option;
use Thinktomorrow\Trader\Catalog\Options\Domain\Options;
use Thinktomorrow\Trader\Catalog\Products\Domain\Product;
use Thinktomorrow\Trader\Catalog\Products\Domain\ProductGroup;
use Thinktomorrow\Trader\Catalog\Taxa\Domain\Taxon;
use Thinktomorrow\Trader\Common\Domain\HasDataAttribute;
use Thinktomorrow\Trader\Catalog\Options\Application\FindProductByOptions;

class DefaultProductGroup implements ProductGroup
{
    use HasDataAttribute;

    private string $id;
    private Collection $products;
    private ?array $gridProductIds;
    private Collection $taxonomy;
    private Options $options;
    private array $data;

    public function __construct(string $id, Collection $products, ?array $gridProductIds = null, Collection $taxonomy, Options $options, array $data)
    {
        $this->id = $id;
        $this->products = $products;
        $this->gridProductIds = $gridProductIds;
        $this->taxonomy = $taxonomy;
        $this->options = $options;
        $this->data = $data;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function findProductForOption(Product $currentProduct, Option $option): ?Product
    {
        return (new FindProductByOptions())->find($this, $currentProduct, $option);
    }

    public function getGridProducts(): Collection
    {
        if (is_array($this->gridProductIds)) {
            return $this->products->filter(fn ($product) => in_array($product->getId(), $this->gridProductIds));
        }

        return $this->products->filter(fn ($product) => $product->isGridProduct());
    }

    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function getTaxonomy(): Collection
    {
        return $this->taxonomy;
    }

    /**
     * The main taxon where the products are structured in.
     *
     * @return Taxon|null
     */
    public function getCatalogTaxon(): ?Taxon
    {
        if ($this->taxonomy->isEmpty()) {
            return null;
        }

        /** @var Taxon $taxon */
        foreach ($this->taxonomy as $taxon) {
            if (in_array(app('trader_config')->getCatalogRootId(), [$taxon->getId(), $taxon->getRootNode()->getId()])) {
                return $taxon;
            }
        }

        return $this->taxonomy->first();
    }

    public function getOptions(): Options
    {
        return $this->options;

//        $options = [];
//
//        $this->products->each(function (Product $product) use (&$options) {
//            foreach ($product->getOptions() as $optionKey => $optionValue) {
//                if (! isset($options[$optionKey])) {
//                    $options[$optionKey] = [];
//                }
//
//                if (! in_array($optionValue, $options[$optionKey])) {
//                    $options[$optionKey][] = $optionValue;
//                }
//            }
//        });
//
//        return $options;
    }

    public function getTitle(): ?string
    {
        return $this->data('title');
    }

    public function getIntro(): ?string
    {
        return $this->data('intro');
    }

    public function getContent(): ?string
    {
        return $this->data('content');
    }

    public function getImages(): Collection
    {
        return collect($this->data('images', []));
    }

    public function isVisitable(): bool
    {
        return $this->data('is_visitable', true);
    }
}
