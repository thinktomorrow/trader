<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Domain\Model\Product\Product;
use Thinktomorrow\Trader\Domain\Model\Product\ProductTaxa\ProductTaxon;
use Thinktomorrow\Trader\Domain\Model\Product\ProductTaxa\VariantProperty;
use Thinktomorrow\Trader\Domain\Model\Taxon\Taxon;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\Taxonomy;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyId;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyType;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlProductRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlTaxonomyRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlTaxonRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlVariantRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryProductRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryTaxonomyRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryTaxonRepository;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;

final class ProductTaxonRepositoryTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('products')]
    public function test_it_can_get_product_taxa_by_product(Product $product)
    {
        $count = count($product->getProductTaxa());

        foreach ($this->repositories() as $i => $repository) {

            // Create taxon data
            $taxonomy = Taxonomy::create(TaxonomyId::fromString('ooo'), TaxonomyType::variant_property);
            $taxonomy2 = Taxonomy::create(TaxonomyId::fromString('ppp'), TaxonomyType::property);
            $taxon = Taxon::create(TaxonId::fromString('xxx'), TaxonomyId::fromString('ooo'));
            $taxon2 = Taxon::create(TaxonId::fromString('yyy'), TaxonomyId::fromString('ooo'));
            $taxon3 = Taxon::create(TaxonId::fromString('zzz'), TaxonomyId::fromString('ooo'));
            $this->taxonomyRepositories()[$i]->save($taxonomy);
            $this->taxonomyRepositories()[$i]->save($taxonomy2);
            $this->taxonRepositories()[$i]->save($taxon);
            $this->taxonRepositories()[$i]->save($taxon2);
            $this->taxonRepositories()[$i]->save($taxon3);

            $repository->save($product);
            $product->releaseEvents();
            $product = $repository->find($product->productId);

            $this->assertCount($count, $product->getProductTaxa());
            $this->assertContainsOnlyInstancesOf(ProductTaxon::class, $product->getProductTaxa());
        }
    }

    #[DataProvider('products')]
    public function test_it_can_get_variant_properties_by_product(Product $product)
    {
        $count = count($product->getVariantProperties());

        foreach ($this->repositories() as $i => $repository) {

            // Create taxon data
            $taxonomy = Taxonomy::create(TaxonomyId::fromString('ooo'), TaxonomyType::variant_property);
            $taxonomy2 = Taxonomy::create(TaxonomyId::fromString('ppp'), TaxonomyType::property);
            $taxon = Taxon::create(TaxonId::fromString('xxx'), TaxonomyId::fromString('ooo'));
            $taxon2 = Taxon::create(TaxonId::fromString('yyy'), TaxonomyId::fromString('ooo'));
            $taxon3 = Taxon::create(TaxonId::fromString('zzz'), TaxonomyId::fromString('ooo'));
            $this->taxonomyRepositories()[$i]->save($taxonomy);
            $this->taxonomyRepositories()[$i]->save($taxonomy2);
            $this->taxonRepositories()[$i]->save($taxon);
            $this->taxonRepositories()[$i]->save($taxon2);
            $this->taxonRepositories()[$i]->save($taxon3);

            $repository->save($product);
            $product->releaseEvents();
            $product = $repository->find($product->productId);

            $this->assertCount($count, $product->getVariantProperties());
            $this->assertContainsOnlyInstancesOf(VariantProperty::class, $product->getVariantProperties());
        }
    }

    #[DataProvider('products')]
    public function test_it_can_get_variant_properties_by_variant(Product $product, int $count)
    {
        foreach ($this->repositories() as $i => $repository) {

            // Create taxon data
            $taxonomy = Taxonomy::create(TaxonomyId::fromString('ooo'), TaxonomyType::variant_property);
            $taxonomy2 = Taxonomy::create(TaxonomyId::fromString('ppp'), TaxonomyType::property);
            $taxon = Taxon::create(TaxonId::fromString('xxx'), TaxonomyId::fromString('ooo'));
            $taxon2 = Taxon::create(TaxonId::fromString('yyy'), TaxonomyId::fromString('ooo'));
            $taxon3 = Taxon::create(TaxonId::fromString('zzz'), TaxonomyId::fromString('ooo'));
            $this->taxonomyRepositories()[$i]->save($taxonomy);
            $this->taxonomyRepositories()[$i]->save($taxonomy2);
            $this->taxonRepositories()[$i]->save($taxon);
            $this->taxonRepositories()[$i]->save($taxon2);
            $this->taxonRepositories()[$i]->save($taxon3);

            $repository->save($product);
            $product->releaseEvents();
            $variant = $repository->find($product->productId)->getVariants()[0];

            $this->assertCount($count, $variant->getVariantProperties());
            $this->assertContainsOnlyInstancesOf(\Thinktomorrow\Trader\Domain\Model\Product\VariantTaxa\VariantProperty::class, $variant->getVariantProperties());
        }
    }

    private static function repositories(): \Generator
    {
        yield new InMemoryProductRepository();
        yield new MysqlProductRepository(new MysqlVariantRepository(new TestContainer()));
    }

    public static function products(): \Generator
    {
        yield [static::createProductWithProductVariantProperties(), 2];
        yield [static::createProductWithVariant(), 0];
    }

    private function taxonomyRepositories(): array
    {
        return [
            new InMemoryTaxonomyRepository(),
            new MysqlTaxonomyRepository(),
        ];
    }

    private function taxonRepositories(): array
    {
        return [
            new InMemoryTaxonRepository(),
            new MysqlTaxonRepository(),
        ];
    }
}
