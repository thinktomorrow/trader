<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Domain\Model\Product\ProductTaxa\ProductTaxon;
use Thinktomorrow\Trader\Domain\Model\Taxon\Taxon;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\Taxonomy;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyId;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyType;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlProductRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlTaxonomyRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlTaxonRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlTaxonTreeRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlVariantRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryProductRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryTaxonomyRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryTaxonRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryTaxonTreeRepository;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;
use Thinktomorrow\Trader\Infrastructure\Test\TestTraderConfig;

final class TaxonTreeWithProductCountRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_get_total_of_products()
    {
        $product = $this->createProductWithVariant();

        foreach ($this->repositories() as $i => $repository) {

            // Create taxon data
            $taxonomy = Taxonomy::create(TaxonomyId::fromString('ooo'), TaxonomyType::variant_property);
            $taxon = Taxon::create(TaxonId::fromString('xxx'), TaxonomyId::fromString('ooo'));
            $this->taxonomyRepositories()[$i]->save($taxonomy);
            $this->taxonRepositories()[$i]->save($taxon);

            $product->updateProductTaxa([
                ProductTaxon::create($product->productId, TaxonId::fromString('xxx')),
            ]);

            $repository->save($product);

            // Hardcoded in memory repository to simulate product-taxons relation
            (new InMemoryTaxonRepository())->setProductIds(TaxonId::fromString('xxx'), [$product->productId->get()]);
            (new InMemoryTaxonRepository())->setOnlineProductIds(TaxonId::fromString('xxx'), [$product->productId->get()]);

            $taxonNode = $this->taxonTreeRepositories()[$i]->findTaxonById('xxx');

            $this->assertCount(1, $taxonNode->getProductIds());
            $this->assertCount(1, $taxonNode->getOnlineProductIds());
            $this->assertEquals(1, $taxonNode->getProductTotal());
        }
    }

    public function test_it_can_get_count_of_products()
    {
        $product = $this->createProductWithVariant();

        foreach ($this->repositories() as $i => $repository) {

            // Create taxon data
            $taxonomy = Taxonomy::create(TaxonomyId::fromString('ooo'), TaxonomyType::variant_property);
            $taxon = Taxon::create(TaxonId::fromString('xxx'), TaxonomyId::fromString('ooo'));
            $this->taxonomyRepositories()[$i]->save($taxonomy);
            $this->taxonRepositories()[$i]->save($taxon);

            $product->updateProductTaxa([
                ProductTaxon::create($product->productId, TaxonId::fromString('xxx')),
            ]);

            $repository->save($product);

            (new InMemoryTaxonRepository())->setProductIds(TaxonId::fromString('xxx'), [$product->productId->get()]);
            (new InMemoryTaxonRepository())->setOnlineProductIds(TaxonId::fromString('xxx'), [$product->productId->get()]);

            $taxonNode = $this->taxonTreeRepositories()[$i]->findTaxonById('xxx');

            $this->assertEquals(1, $taxonNode->getProductCount([$product->productId->get()]));
            $this->assertEquals(0, $taxonNode->getProductCount(['non-existing-product-id']));
        }
    }

    private static function repositories(): \Generator
    {
        yield new MysqlProductRepository(new MysqlVariantRepository(new TestContainer()));
        yield new InMemoryProductRepository();
    }

    public static function products(): \Generator
    {
        yield [static::createProductWithProductVariantProperties(), 3, 1];
    }

    private function taxonomyRepositories(): array
    {
        return [
            new MysqlTaxonomyRepository(),
            new InMemoryTaxonomyRepository(),
        ];
    }

    private function taxonRepositories(): array
    {
        return [
            new MysqlTaxonRepository(),
            new InMemoryTaxonRepository(),
        ];
    }

    private function taxonTreeRepositories(): array
    {
        return [
            new MysqlTaxonTreeRepository(new TestContainer, new TestTraderConfig),
            new InMemoryTaxonTreeRepository(new TestContainer, new TestTraderConfig),
        ];
    }
}
