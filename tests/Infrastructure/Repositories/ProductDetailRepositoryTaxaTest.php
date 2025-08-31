<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Application\Common\DefaultLocale;
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
                ProductTaxon::create($product->productId, $taxon->taxonId),
            ]);

            $productRepository = iterator_to_array($this->productRepositories())[$i];
            $productRepository->save($product);
            $product->releaseEvents();

            $result = $repository
                ->findProductDetail($product->getVariants()[0]->variantId)
                ->getTaxa();

            $this->assertCount(1, $result);

            $taxonItem = $result[0];

            $this->assertEquals($product->productId->get(), $taxonItem->getProductId());
            $this->assertEquals($taxon->taxonId->get(), $taxonItem->getTaxonId());
            $this->assertEquals($taxon->taxonomyId->get(), $taxonItem->getTaxonomyId());
            $this->assertEquals($taxonomy->getType()->value, $taxonItem->getTaxonomyType());
            $this->assertTrue($taxonItem->showsInGrid());

            $this->assertEquals('Taxon title', $taxonItem->getLabel('nl'));
            $this->assertEquals('Titre du taxon', $taxonItem->getLabel('fr'));
        }
    }

    #[DataProvider('products')]
    public function test_it_can_get_taxon_keys_by_product(Product $product)
    {
        foreach ($this->repositories() as $i => $repository) {

            // Create test data
            $taxonomy = Taxonomy::create(TaxonomyId::fromString('ooo'), TaxonomyType::property);
            $taxonomy->showInGrid();

            $taxon = Taxon::create(TaxonId::fromString('xxx'), TaxonomyId::fromString('ooo'));
            $taxon->updateTaxonKeys([
                TaxonKey::create($taxon->taxonId, TaxonKeyId::fromString('key-' . $taxon->taxonId->get() . '-nl'), Locale::fromString('nl')),
                TaxonKey::create($taxon->taxonId, TaxonKeyId::fromString('key-' . $taxon->taxonId->get() . '-fr'), Locale::fromString('fr')),
            ]);

            $this->taxonomyRepositories()[$i]->save($taxonomy);
            $this->taxonRepositories()[$i]->save($taxon);

            $product->updateProductTaxa([
                ProductTaxon::create($product->productId, $taxon->taxonId),
            ]);

            $productRepository = iterator_to_array($this->productRepositories())[$i];
            $productRepository->save($product);
            $product->releaseEvents();

            $result = $repository
                ->findProductDetail($product->getVariants()[0]->variantId)
                ->getTaxa();
            $this->assertCount(1, $result);

            $taxonItem = $result[0];

            $this->assertEquals('key-xxx-nl', $taxonItem->getKey('nl'));
            $this->assertEquals('key-xxx-fr', $taxonItem->getKey('fr'));
            $this->assertEquals('key-xxx-nl', $taxonItem->getUrl('nl'));
            $this->assertEquals('key-xxx-fr', $taxonItem->getUrl('fr'));

            DefaultLocale::set(Locale::fromString('nl'));
            $this->assertEquals('key-xxx-nl', $taxonItem->getKey());
            $this->assertEquals('key-xxx-nl', $taxonItem->getUrl());
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
