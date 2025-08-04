<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Model\Product\ProductTaxa\ProductTaxon;
use Thinktomorrow\Trader\Domain\Model\Taxon\Taxon;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonKey;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonKeyId;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\Taxonomy;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyId;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyType;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlProductRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlTaxonomyRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlTaxonRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlVariantRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryProductRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryProductTaxonRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryTaxonomyRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryTaxonRepository;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;

final class ProductTaxonRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        InMemoryProductTaxonRepository::clear();
    }

    public function test_it_can_get_product_taxa_by_taxon_ids()
    {
        foreach ($this->productTaxonRepositories() as $i => $taxonRepository) {

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

            $product = static::createProduct();
            $product->updateProductTaxa([
                ProductTaxon::create($product->productId, $taxonomy->taxonomyId, $taxon->taxonId),
            ]);

            $this->productRepositories()[$i]->save($product);

            // Account for in-memory repositories
            InMemoryProductTaxonRepository::$productTaxonLookup[$product->productId->get()] = [
                $taxon->taxonId->get(),
            ];

            $result = $taxonRepository->getTaxaByProduct($product->productId->get());

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

    private function productTaxonRepositories(): \Generator
    {
        yield new InMemoryProductTaxonRepository();
        yield new MysqlProductRepository(new MysqlVariantRepository(new TestContainer()));
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

    private function productRepositories(): array
    {
        return [
            new InMemoryProductRepository(),
            new MysqlProductRepository(new MysqlVariantRepository(new TestContainer())),
        ];
    }
}
