<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Domain\Model\Product\Product;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\Variant;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
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
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryVariantRepository;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;

final class VariantRepositoryTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('variants')]
    public function test_it_can_save_and_find_an_variant(Product $product, Variant $variant)
    {
        foreach ($this->repositories() as $i => $repository) {

            // Create taxon data
            $taxonomy = Taxonomy::create(TaxonomyId::fromString('ooo'), TaxonomyType::variant_property);
            $taxon = Taxon::create(TaxonId::fromString('xxx'), TaxonomyId::fromString('ooo'));
            $this->taxonomyRepositories()[$i]->save($taxonomy);
            $this->taxonRepositories()[$i]->save($taxon);

            $this->productRepositories()[$i]->save($product);
            $repository->save($variant);

            $variantStates = $repository->getStatesByProduct($variant->productId);

            $this->assertEquals([$variant], array_map(fn($variantState) => Variant::fromMappedData($variantState[0], ['product_id' => 'xxx'], $variantState[1]), $variantStates));
        }
    }

    #[DataProvider('variants')]
    public function test_it_can_update_variant_taxa(Product $product, Variant $variant)
    {
        foreach ($this->repositories() as $i => $repository) {

            // Create taxon data
            $taxonomy = Taxonomy::create(TaxonomyId::fromString('ooo'), TaxonomyType::variant_property);
            $taxon = Taxon::create(TaxonId::fromString('xxx'), TaxonomyId::fromString('ooo'));
            $this->taxonomyRepositories()[$i]->save($taxonomy);
            $this->taxonRepositories()[$i]->save($taxon);

            $this->productRepositories()[$i]->save($product);
            $repository->save($variant);

            // Resave so that the sync check occurs
            $repository->save($variant);

            $this->assertEquals($variant->getChildEntities(), $repository->getStatesByProduct($variant->productId)[0][1]);
        }
    }

    #[DataProvider('variants')]
    public function test_it_can_delete_an_variant(Product $product, Variant $variant)
    {
        foreach ($this->repositories() as $i => $repository) {
            $this->productRepositories()[$i]->save($product);
            $repository->save($variant);
            $repository->delete($variant->variantId);

            $this->assertCount(0, $repository->getStatesByProduct($variant->productId));
        }
    }

    public function test_it_can_generate_a_next_reference()
    {
        foreach ($this->repositories() as $repository) {
            $this->assertInstanceOf(VariantId::class, $repository->nextReference());
        }
    }

    #[DataProvider('variants')]
    public function test_it_can_find_variant_for_cart(Product $product, Variant $variant)
    {
        foreach ($this->repositories() as $i => $repository) {
            $this->productRepositories()[$i]->save($product);

            $this->assertNotNull($repository->findVariantForCart($variant->variantId));
        }
    }

    #[DataProvider('variants')]
    public function test_it_can_find_all_variants_for_cart(Product $product, Variant $variant)
    {
        foreach ($this->repositories() as $i => $repository) {
            $this->productRepositories()[$i]->save($product);

            $this->assertNotNull($repository->findAllVariantsForCart([$variant->variantId]));
        }
    }

    private function repositories(): \Generator
    {
        yield new InMemoryVariantRepository();
        yield new MysqlVariantRepository(new TestContainer());
    }

    private function productRepositories(): array
    {
        return [
            new InMemoryProductRepository(),
            new MysqlProductRepository(new MysqlVariantRepository(new TestContainer())),
        ];
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

    public static function variants(): \Generator
    {
        $product = static::createProductWithVariant();

        yield [
            $product,
            $product->getVariants()[0],
        ];

        $product = static::createProductWithPersonalisations();

        yield [
            $product,
            $product->getVariants()[0],
        ];
    }
}
