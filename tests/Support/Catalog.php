<?php

namespace Tests\Support;

use Thinktomorrow\Trader\Application\Product\ProductDetail\ProductDetail;
use Thinktomorrow\Trader\Application\Product\Taxa\ProductTaxonItem;
use Thinktomorrow\Trader\Application\Product\Taxa\VariantTaxonItem;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonNode;
use Thinktomorrow\Trader\Application\Taxonomy\TaxonomyItem;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Model\Product\Personalisation\Personalisation;
use Thinktomorrow\Trader\Domain\Model\Product\Personalisation\PersonalisationId;
use Thinktomorrow\Trader\Domain\Model\Product\Personalisation\PersonalisationType;
use Thinktomorrow\Trader\Domain\Model\Product\Product;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\ProductState;
use Thinktomorrow\Trader\Domain\Model\Product\ProductTaxa\ProductTaxon;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\Variant;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantSalePrice;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantUnitPrice;
use Thinktomorrow\Trader\Domain\Model\Product\VariantTaxa\VariantTaxon;
use Thinktomorrow\Trader\Domain\Model\Taxon\Taxon;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonKey;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonKeyId;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\Taxonomy;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyId;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyType;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\DefaultProductDetail;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\DefaultProductTaxonItem;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\DefaultTaxonNode;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\DefaultTaxonomyItem;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\DefaultVariantTaxonItem;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;

class Catalog
{
    public readonly CatalogRepositories $repos;

    public bool $persist = true;

    public function __construct(CatalogRepositories $repos)
    {
        $this->repos = $repos;
    }

    public function dontPersist(): self
    {
        $this->persist = false;

        return $this;
    }

    public function persist(): self
    {
        $this->persist = true;

        return $this;
    }

    public static function setUp(): void
    {
        (new TestContainer())->add(ProductDetail::class, DefaultProductDetail::class);
        (new TestContainer())->add(ProductTaxonItem::class, DefaultProductTaxonItem::class);
        (new TestContainer())->add(VariantTaxonItem::class, DefaultVariantTaxonItem::class);
        (new TestContainer())->add(TaxonomyItem::class, DefaultTaxonomyItem::class);
        (new TestContainer())->add(TaxonNode::class, DefaultTaxonNode::class);
    }

    public static function tearDown(): void
    {
        InMemoryCatalogRepositories::clear();
    }

    public static function drivers(): array
    {
        return [
            self::inMemory(),
            self::mysql(),
        ];
    }

    public static function inMemory(): self
    {
        return new self(new InMemoryCatalogRepositories());
    }

    public static function mysql(): self
    {
        return new self(new MysqlCatalogRepositories(new TestContainer()));
    }

    public function createTaxonomy(string $taxonomyId = 'taxonomy-aaa', string $type = TaxonomyType::category->value): Taxonomy
    {
        $taxonomy = Taxonomy::create(TaxonomyId::fromString($taxonomyId), TaxonomyType::from($type));
        $taxonomy->showAsGridFilter();
        $taxonomy->addData(['title' => ['nl' => $taxonomyId . ' title nl', 'fr' => $taxonomyId . ' title fr']]);

        $this->saveTaxonomy($taxonomy);

        return $taxonomy;
    }

    public function createTaxon(string $taxonId = 'taxon-aaa', string $taxonomyId = 'taxonomy-aaa', ?string $parentId = null): Taxon
    {
        $taxon = Taxon::create(TaxonId::fromString($taxonId), TaxonomyId::fromString($taxonomyId), $parentId ? TaxonId::fromString($parentId) : null);
        $taxon->addData(['title' => ['nl' => $taxonId . ' title nl', 'fr' => $taxonId . ' title fr']]);

        $taxon->updateTaxonKeys([
            TaxonKey::create($taxon->taxonId, TaxonKeyId::fromString($taxonId . '-key-nl'), Locale::fromString('nl')),
            TaxonKey::create($taxon->taxonId, TaxonKeyId::fromString($taxonId . '-key-fr'), Locale::fromString('fr')),
        ]);

        $this->saveTaxon($taxon);

        return $taxon;
    }

    public function createProduct(string $productId = 'product-aaa', string $variantId = 'variant-aaa'): Product
    {
        $product = Product::create(ProductId::fromString($productId));
        $product->updateState(ProductState::online);

        if ($this->persist) {
            $this->saveProduct($product);
        }

        $this->createVariant($product, $variantId);

        return $product;
    }

    public function createVariant(string|Product $productId = 'product-aaa', string $variantId = 'variant-aaa'): Variant
    {
        $product = $productId instanceof Product ? $productId : $this->repos->productRepository()->find(ProductId::fromString($productId));

        $variant = Variant::create(
            $product->productId,
            VariantId::fromString($variantId),
            VariantUnitPrice::fromScalars(100, '20', false),
            VariantSalePrice::fromScalars(80, '20', false),
            'sku-' . $variantId
        );

        $variant->showInGrid();

        $product->createVariant($variant);

        if ($this->persist) {
            $this->saveProduct($product);
        }

        return $variant;
    }

    public function saveTaxonomy(Taxonomy $taxonomy): void
    {
        $this->repos->taxonomyRepository()->save($taxonomy);
    }

    public function saveTaxon(Taxon $taxon): void
    {
        $this->repos->taxonRepository()->save($taxon);
    }

    public function saveProduct(Product $product): void
    {
        $this->repos->productRepository()->save($product);
    }

    public function linkProductToTaxon(string|Product $productId, string|Taxon $taxonId): Product
    {
        $product = $productId instanceof Product ? $productId : $this->repos->productRepository()->find(ProductId::fromString($productId));
        $taxonId = $taxonId instanceof Taxon ? $taxonId->taxonId->get() : $taxonId;

        $product->updateProductTaxa([
            ...$product->getProductTaxa(),
            ProductTaxon::create($product->productId, TaxonId::fromString($taxonId)),
        ]);

        if ($this->persist) {
            $this->repos->productRepository()->save($product);
        }

        return $product;
    }

    public function linkVariantToTaxon(string $productId, string $variantId, string $taxonId): Product
    {
        $product = $this->repos->productRepository()->find(ProductId::fromString($productId));
        $variant = $product->findVariant(VariantId::fromString($variantId));

        $variant->updateVariantTaxa([
            ...$variant->getVariantTaxa(),
            VariantTaxon::create(VariantId::fromString($variantId), TaxonId::fromString($taxonId)),
        ]);

        if ($this->persist) {
            $this->repos->productRepository()->save($product);
        }

        return $product;
    }

    public function makePersonalisation(string $productId = 'product-aaa', string $personalisationId = 'personalisation-aaa'): Personalisation
    {
        return Personalisation::create(
            ProductId::fromString($productId),
            PersonalisationId::fromString($personalisationId),
            PersonalisationType::fromString(PersonalisationType::TEXT),
            ['foo' => 'bar']
        );
    }

    public function addPersonalisationToProduct(Product $product, Personalisation $personalisation): Product
    {
        $product->updatePersonalisations([
            $personalisation,
        ]);

        return $product;
    }
}
