<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Domain\Model\Product\Product;
use Thinktomorrow\Trader\Domain\Model\Product\ProductTaxa\ProductTaxon;
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
        foreach ($this->repositories() as $i => $repository) {

            // Create taxon data
            $taxonomy = Taxonomy::create(TaxonomyId::fromString('ooo'), TaxonomyType::variant_property);
            $taxon = Taxon::create(TaxonId::fromString('xxx'), TaxonomyId::fromString('ooo'));
            $this->taxonomyRepositories()[$i]->save($taxonomy);
            $this->taxonRepositories()[$i]->save($taxon);

            $repository->save($product);
            $product->releaseEvents();
            $product = $repository->find($product->productId);

            $this->assertCount(count($product->getProductTaxa()), $repository->getProductTaxonStatesByProduct($product->productId->get()));
            $this->assertContainsOnlyArray($repository->getProductTaxonStatesByProduct($product->productId->get()));
        }
    }

    #[DataProvider('products')]
    public function test_it_can_get_product_taxa_by_given_taxon_ids(Product $product)
    {
        foreach ($this->repositories() as $i => $repository) {

            // Create taxon data
            $taxonomy = Taxonomy::create(TaxonomyId::fromString('ooo'), TaxonomyType::variant_property);
            $taxon = Taxon::create(TaxonId::fromString('xxx'), TaxonomyId::fromString('ooo'));
            $this->taxonomyRepositories()[$i]->save($taxonomy);
            $this->taxonRepositories()[$i]->save($taxon);

            $repository->save($product);
            $product->releaseEvents();
            $product = $repository->find($product->productId);

            $this->assertCount(1, $repository->getProductTaxaByTaxonIds($product->productId->get(), [$taxon->taxonId->get()]));
            $this->assertContainsOnlyInstancesOf(ProductTaxon::class, $repository->getProductTaxaByTaxonIds($product->productId->get(), [$taxon->taxonId->get()]));
        }
    }

    private static function repositories(): \Generator
    {
        yield new InMemoryProductRepository();
        yield new MysqlProductRepository(new MysqlVariantRepository(new TestContainer()));
    }

    public static function products(): \Generator
    {
        yield [static::createProductWithProductVariantProperties()];
//        yield [static::createProduct()];
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
