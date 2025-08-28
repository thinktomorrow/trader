<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models;

use Thinktomorrow\Trader\Application\Common\HasLocale;
use Thinktomorrow\Trader\Application\Common\RendersData;
use Thinktomorrow\Trader\Application\Common\RendersVariantPrices;
use Thinktomorrow\Trader\Application\Product\Grid\GridItem;
use Thinktomorrow\Trader\Application\Product\Taxa\ProductTaxonItem;
use Thinktomorrow\Trader\Application\Product\Taxa\VariantTaxonItem;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantSalePrice;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantState;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantUnitPrice;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyType;

class DefaultGridItem implements GridItem
{
    use RendersVariantPrices;
    use RendersData;
    use HasLocale;

    protected VariantId $variantId;
    protected ProductId $productId;
    protected VariantState $state;
    protected array $data;
    protected iterable $images;

    /** @var array<ProductTaxonItem|VariantTaxonItem> */
    protected array $taxa;

    final private function __construct()
    {
    }

    public static function fromMappedData(array $state, array $taxa): static
    {
        $item = new static();

        $item->variantId = VariantId::fromString($state['variant_id']);
        $item->productId = ProductId::fromString($state['product_id']);
        $item->state = VariantState::from($state['state']);
        $item->salePrice = VariantSalePrice::fromScalars($state['sale_price'], $state['tax_rate'], $state['includes_vat']);
        $item->unitPrice = VariantUnitPrice::fromScalars($state['unit_price'], $state['tax_rate'], $state['includes_vat']);
        $item->data = array_merge(
            ['product_data' => json_decode($state['product_data'], true)],
            json_decode($state['data'], true),
        );

        foreach ($taxa as $taxon) {
            if (! ($taxon instanceof ProductTaxonItem)) {
                throw new \InvalidArgumentException('Taxa must be instances of ProductTaxonItem or VariantTaxonItem');
            }
        }

        $item->taxa = $taxa;

        return $item;
    }

    public function getVariantId(): string
    {
        return $this->variantId->get();
    }

    public function getProductId(): string
    {
        return $this->productId->get();
    }

    public function isAvailable(): bool
    {
        return in_array($this->state, VariantState::availableStates());
    }

    public function getTitle(): string
    {
        return $this->data('product_data.title', null, '');
    }

    public function getUrl(): string
    {
        return '/' . $this->getVariantId();
    }

    public function setImages(iterable $images): void
    {
        $this->images = $images;
    }

    public function getImages(): iterable
    {
        return $this->images;
    }

    public function getTaxa(): array
    {
        return $this->taxa;
    }

    public function getMainCategory(): ?ProductTaxonItem
    {
        // TODO: how to get the 'main' taxonomy? We should set this in database on a taxonomy instead of in config
        // Then, we can fetch the main taxonomy type and return the first taxon of that type
        // Something as main_category Perhaps? Better is to assign 'main' to a category taxonomy.
        foreach ($this->taxa as $taxon) {
            if ($taxon->getTaxonomyType() === TaxonomyType::category->value && $taxon->showOnline()) {
                return $taxon;
            }
        }

        return null;
    }

    public function getGridCategories(): array
    {
        return array_filter($this->taxa, function (ProductTaxonItem $taxon) {
            return $taxon->getTaxonomyType() === TaxonomyType::category->value && $taxon->showOnline();
        });
    }

    public function getGridProductProperties(): array
    {
        return array_filter($this->taxa, function (ProductTaxonItem $taxon) {
            return $taxon->getTaxonomyType() === TaxonomyType::property->value && $taxon->showOnline();
        });
    }

    public function getGridVariantProperties(): array
    {
        return array_filter($this->taxa, function (ProductTaxonItem $taxon) {
            return $taxon->getTaxonomyType() === TaxonomyType::variant_property->value && $taxon->showOnline();
        });
    }

    public function getGridCollections(): array
    {
        return array_filter($this->taxa, function (ProductTaxonItem $taxon) {
            return $taxon->getTaxonomyType() === TaxonomyType::collection->value && $taxon->showOnline();
        });
    }

    public function getGridTags(): array
    {
        return array_filter($this->taxa, function (ProductTaxonItem $taxon) {
            return $taxon->getTaxonomyType() === TaxonomyType::tag->value && $taxon->showOnline();
        });
    }
}
