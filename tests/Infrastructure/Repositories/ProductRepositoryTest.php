<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Domain\Model\Product\Exceptions\CouldNotFindProduct;
use Thinktomorrow\Trader\Domain\Model\Product\Product;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
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

final class ProductRepositoryTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('products')]
    public function test_it_can_save_and_find_a_product(Product $product)
    {
        foreach ($this->repositories() as $i => $repository) {

            // Create taxon data
            $taxonomy = Taxonomy::create(TaxonomyId::fromString('ooo'), TaxonomyType::variant_property);
            $taxon = Taxon::create(TaxonId::fromString('xxx'), TaxonomyId::fromString('ooo'));
            $this->taxonomyRepositories()[$i]->save($taxonomy);
            $this->taxonRepositories()[$i]->save($taxon);

            $repository->save($product);
            $product->releaseEvents();

            $this->assertEquals($product, $repository->find($product->productId));
        }
    }

    #[DataProvider('products')]
    public function test_it_can_delete_a_product(Product $product)
    {
        $productsNotFound = 0;

        foreach ($this->repositories() as $repository) {
            $repository->save($product);
            $repository->delete($product->productId);

            try {
                $repository->find($product->productId);
            } catch (CouldNotFindProduct $e) {
                $productsNotFound++;
            }
        }

        $this->assertEquals(count(iterator_to_array($this->repositories())), $productsNotFound);
    }

    public function test_it_can_generate_a_next_reference()
    {
        foreach ($this->repositories() as $repository) {
            $this->assertInstanceOf(ProductId::class, $repository->nextReference());
        }
    }

    private static function repositories(): \Generator
    {
        yield new InMemoryProductRepository();
        yield new MysqlProductRepository(new MysqlVariantRepository(new TestContainer()));
    }

    public static function products(): \Generator
    {
        yield [static::createProduct()];
        yield [static::createProductWithPersonalisations()];
        yield [static::createProductWithVariant()];
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
