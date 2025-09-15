<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models;

use Illuminate\Support\Str;
use Thinktomorrow\Trader\Application\Common\HasLocale;
use Thinktomorrow\Trader\Application\Common\RendersData;
use Thinktomorrow\Trader\Application\Common\RendersVariantPrices;
use Thinktomorrow\Trader\Application\Product\ProductDetail\ProductDetail;
use Thinktomorrow\Trader\Application\Product\Taxa\ProductTaxonItem;
use Thinktomorrow\Trader\Application\Product\Taxa\VariantTaxonItem;
use Thinktomorrow\Trader\Application\Stock\Read\StockableDefault;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantSalePrice;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantState;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantUnitPrice;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyType;

class DefaultProductDetail implements ProductDetail
{
    use RendersVariantPrices;
    use RendersData;
    use HasLocale;
    use StockableDefault;

    protected VariantId $variantId;
    protected ProductId $productId;
    protected VariantState $state;
    protected array $taxa;
    protected string $sku;
    protected ?string $ean;
    protected array $data;
    protected iterable $images;

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
        $item->sku = $state['sku'] ?? $state['variant_id'];
        $item->ean = $state['ean'] ?? null;
        $item->data = array_merge(
            ['product_data' => json_decode($state['product_data'], true)],
            ['stock_data' => ($state['stock_data'] ? json_decode($state['stock_data'], true) : [])],
            json_decode($state['data'], true),
        );

        $item->stock_level = $state['stock_level'];
        $item->ignore_out_of_stock = (bool)$state['ignore_out_of_stock'];

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

    public function getTitle(?string $locale = null): string
    {
        $productTitle = $this->dataAsPrimitive('product_data.title', $locale, '');
        $variantTitle = $this->dataAsPrimitive('title', $locale);
        $variantOptionTitle = $this->dataAsPrimitive('option_title', $locale);

        if ($variantTitle) {
            return $variantTitle;
        }

        if (! $variantOptionTitle || $productTitle == $variantOptionTitle) {
            return $productTitle;
        }
        if (! $productTitle) {
            return $variantOptionTitle;
        }

        return $productTitle . ' ' . $variantOptionTitle;
    }

    public function getIntro(?string $locale = null): string
    {
        return Str::limit($this->getContent($locale), 160, '...');
    }

    public function getContent(?string $locale = null): string
    {
        return $this->dataAsPrimitive('product_data.content', $locale, '');
    }

    public function getSku(): string
    {
        return $this->sku;
    }

    public function getEan(): ?string
    {
        return $this->ean;
    }

    public function getUrl(?string $locale = null): string
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

    public static function stateSelect(): array
    {
        return [];
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

    public function getCategories(): array
    {
        return array_filter($this->taxa, function (ProductTaxonItem $taxon) {
            return $taxon->getTaxonomyType() === TaxonomyType::category->value && $taxon->showOnline();
        });
    }

    public function getGoogleCategories(): array
    {
        return array_filter($this->taxa, function (ProductTaxonItem $taxon) {
            return $taxon->getTaxonomyType() === TaxonomyType::google_category->value && $taxon->showOnline();
        });
    }

    public function getProductProperties(): array
    {
        return array_filter($this->taxa, function (ProductTaxonItem $taxon) {
            return $taxon->getTaxonomyType() === TaxonomyType::property->value && $taxon->showOnline();
        });
    }

    /**
     * All available variant properties of the product
     * @return array<ProductTaxonItem>
     */
    public function getProductVariantProperties(): array
    {
        return array_values(array_filter($this->taxa, function (ProductTaxonItem $taxon) {
            return (! $taxon instanceof VariantTaxonItem) && $taxon->getTaxonomyType() === TaxonomyType::variant_property->value && $taxon->showOnline();
        }));
    }

    /**
     * @return array<VariantTaxonItem>
     */
    public function getVariantProperties(): array
    {
        return array_values(array_filter($this->taxa, function (ProductTaxonItem $taxon) {
            return $taxon instanceof VariantTaxonItem && $taxon->getTaxonomyType() === TaxonomyType::variant_property->value && $taxon->showOnline();
        }));
    }

    public function getCollections(): array
    {
        return array_filter($this->taxa, function (ProductTaxonItem $taxon) {
            return $taxon->getTaxonomyType() === TaxonomyType::collection->value && $taxon->showOnline();
        });
    }

    public function getTags(): array
    {
        return array_filter($this->taxa, function (ProductTaxonItem $taxon) {
            return $taxon->getTaxonomyType() === TaxonomyType::tag->value && $taxon->showOnline();
        });
    }
}
