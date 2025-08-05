<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Model\Product\Product;
use Thinktomorrow\Trader\Domain\Model\Product\ProductTaxa\ProductTaxon;
use Thinktomorrow\Trader\Domain\Model\Taxon\Taxon;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonKey;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonKeyId;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\Taxonomy;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyId;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyType;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlProductDetailRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlProductRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlTaxonomyRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlTaxonRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlVariantRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryProductDetailRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryProductRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryTaxonomyRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryTaxonRepository;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;

final class ProductDetailRepositoryTaxaTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('products')]
    public function test_it_can_get_product_taxa(Product $product)
    {
        foreach ($this->repositories() as $i => $repository) {

            // Create test data
            $taxonomy = Taxonomy::create(TaxonomyId::fromString('ooo'), TaxonomyType::property);
            $taxonomy->showInGrid();

            $taxon = Taxon::create(TaxonId::fromString('xxx'), TaxonomyId::fromString('ooo'));
            $taxon->changeOrder(2);
            $taxon->updateTaxonKeys([TaxonKey::create($taxon->taxonId, TaxonKeyId::fromString('taxon-key'), Locale::fromString('nl'))]);
            $taxon->addData(['title' => [
                'nl' => 'Taxon title',
                'fr' => 'Titre du taxon',
            ]]);

            $this->taxonomyRepositories()[$i]->save($taxonomy);
            $this->taxonRepositories()[$i]->save($taxon);

            $product->updateProductTaxa([
                ProductTaxon::create($product->productId, $taxonomy->taxonomyId, $taxonomy->getType(), $taxon->taxonId),
            ]);

            $productRepository = iterator_to_array($this->productRepositories())[$i];
            $productRepository->save($product);
            $product->releaseEvents();

            $result = $repository
                ->findProductDetail($product->getVariants()[0]->variantId)
                ->getProductTaxa();

            $this->assertCount(1, $result);

            $this->assertEquals($product->productId->get(), $result[0]->getProductId());
            $this->assertEquals($taxon->taxonId->get(), $result[0]->getTaxonId());
            $this->assertEquals($taxon->taxonomyId->get(), $result[0]->getTaxonomyId());
            $this->assertEquals($taxonomy->getType()->value, $result[0]->getTaxonomyType());
            $this->assertTrue($result[0]->showsInGrid());
            $this->assertEquals(2, $result[0]->getOrder());

            $this->assertEquals('Taxon title', $result[0]->getLabel('nl'));
            $this->assertEquals('Titre du taxon', $result[0]->getLabel('fr'));
        }
    }

    private function productRepositories(): \Generator
    {
        yield new InMemoryProductRepository();
        yield new MysqlProductRepository(new MysqlVariantRepository(new TestContainer()));
    }

    private function repositories(): \Generator
    {
        yield new InMemoryProductDetailRepository();
        yield new MysqlProductDetailRepository(new TestContainer());
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

    public static function products(): \Generator
    {
        yield [static::createProductWithVariant()];
    }
}
