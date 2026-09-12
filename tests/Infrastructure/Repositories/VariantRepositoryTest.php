<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Illuminate\Support\Facades\DB;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Application\Cart\CartApplication;
use Thinktomorrow\Trader\Application\Cart\Line\AddLine;
use Thinktomorrow\Trader\Application\Cart\RefreshCart\RefreshCart;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\Variant;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Product\VariantTaxa\VariantProperty;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyType;
use Thinktomorrow\Trader\Testing\Catalog\CatalogContext;

final class VariantRepositoryTest extends TestCase
{
    public function test_it_can_save_and_find_a_variant()
    {
        /** @var CatalogContext $catalog */
        foreach (CatalogContext::drivers() as $catalog) {

            // Create taxon data
            $taxonomy = $catalog->createTaxonomy('taxonomy-aaa', TaxonomyType::variant_property->value);
            $taxon = $catalog->createTaxon();
            $product = $catalog->createProduct();
            $variant = $product->getVariants()[0];

            $variantStates = $catalog->repos()->variantRepository()->getStatesByProduct($product->productId);

            $this->assertEquals([$variant], array_map(fn ($variantState) => Variant::fromMappedData($variantState[0], ['product_id' => $product->productId->get()], $variantState[1]), $variantStates));
        }
    }

    public function test_it_can_update_variant_taxa()
    {
        /** @var CatalogContext $catalog */
        foreach (CatalogContext::drivers() as $catalog) {

            // Create taxon data
            $catalog->createTaxonomy('taxonomy-aaa', TaxonomyType::variant_property->value);
            $taxon = $catalog->createTaxon();
            $product = $catalog->createProduct();
            $variant = $product->getVariants()[0];

            $variant->updateVariantTaxa([
                VariantProperty::create($variant->variantId, $taxon->taxonId),
            ]);

            $product->updateVariant($variant);
            $catalog->saveProduct($product);

            $variantStates = $catalog->repos()->variantRepository()->getStatesByProduct($product->productId);

            $this->assertEquals([$variant], array_map(fn ($variantState) => Variant::fromMappedData($variantState[0], ['product_id' => $product->productId->get()], $variantState[1]), $variantStates));
        }
    }

    public function test_it_can_delete_an_variant()
    {
        $recordsNotFound = 0;

        /** @var CatalogContext $catalog */
        foreach (CatalogContext::drivers() as $catalog) {

            $repository = $catalog->repos()->variantRepository();

            $product = $catalog->createProduct();
            $variant = $product->getVariants()[0];

            $repository->delete($variant->variantId);

            if (count($repository->getStatesByProduct($product->productId)) < 1) {
                $recordsNotFound++;
            }
        }

        $this->assertCount($recordsNotFound, CatalogContext::drivers());
    }

    public function test_it_can_generate_a_next_reference()
    {
        /** @var CatalogContext $catalog */
        foreach (CatalogContext::drivers() as $catalog) {

            $repository = $catalog->repos()->variantRepository();

            $this->assertInstanceOf(VariantId::class, $repository->nextReference());
        }
    }

    public function test_it_can_find_all_variants_for_cart()
    {
        /** @var CatalogContext $catalog */
        foreach (CatalogContext::drivers() as $catalog) {

            $product = $catalog->createProduct();
            $variant = $product->getVariants()[0];

            $repository = $catalog->repos()->variantRepository();

            $this->assertNotNull($repository->findAllVariantsForCart([$variant->variantId]));
        }
    }

    public function test_bulk_cart_variants_keep_personalisations_for_each_product(): void
    {
        $catalog = $this->catalogContext;
        $first = $catalog->createProduct('first-product', 'first-variant');
        $second = $catalog->createProduct('second-product', 'second-variant');
        $catalog->addPersonalisationToProduct($first, $catalog->makePersonalisation('first-product', 'first-personalisation'));
        $catalog->addPersonalisationToProduct($second, $catalog->makePersonalisation('second-product', 'second-personalisation'));
        $catalog->saveProduct($first);
        $catalog->saveProduct($second);

        $variants = $catalog->repos()->variantRepository()->findAllVariantsForCart(['first-variant', 'second-variant']);

        $this->assertCount(2, $variants);
        $personalisations = [];
        foreach ($variants as $variant) {
            $personalisations[$variant->getProductId()->get()] = array_values(array_map(fn ($field) => $field->personalisationId->get(), $variant->getPersonalisations()));
        }
        $this->assertSame(['first-personalisation'], $personalisations['first-product']);
        $this->assertSame(['second-personalisation'], $personalisations['second-product']);
    }

    public function test_empty_cart_variant_list_does_not_query_the_database(): void
    {
        DB::enableQueryLog();
        DB::flushQueryLog();

        $this->assertSame([], $this->catalogContext->repos()->variantRepository()->findAllVariantsForCart([]));
        $this->assertSame([], DB::getQueryLog());
        DB::disableQueryLog();
    }

    public function test_cart_refresh_loads_variants_once_for_prices_and_vat(): void
    {
        $product = $this->catalogContext->createProduct();
        $cartApplication = app(CartApplication::class);
        $orderId = $cartApplication->createNewOrder();
        $cartApplication->addLine(new AddLine($orderId->get(), $product->getVariants()[0]->variantId->get(), 2, [], []));
        DB::enableQueryLog();
        DB::flushQueryLog();

        $cartApplication->refresh(new RefreshCart($orderId->get()));

        $variantQueries = array_filter(DB::getQueryLog(), fn (array $query): bool => str_contains($query['query'], 'from `trader_product_variants`'));
        $personalisationQueries = array_filter(DB::getQueryLog(), fn (array $query): bool => str_contains($query['query'], 'from `trader_product_personalisations`'));
        DB::disableQueryLog();
        $this->assertCount(1, $variantQueries);
        $this->assertCount(1, $personalisationQueries);
        $cart = $this->orderContext->repos()->cartRepository()->findCart($orderId);
        $this->assertSame(2, $cart->getLines()[0]->getQuantity());
    }

    public function test_it_keeps_variant_taxa_with_null_data_when_loading_states(): void
    {
        /** @var CatalogContext $catalog */
        foreach (CatalogContext::drivers() as $catalog) {
            $suffix = uniqid();

            $taxonomy = $catalog->createTaxonomy('taxonomy-'.$suffix, TaxonomyType::variant_property->value);
            $taxon = $catalog->createTaxon('taxon-'.$suffix, $taxonomy->taxonomyId->get());
            $product = $catalog->createProduct('product-'.$suffix, 'variant-'.$suffix);
            $variant = $product->getVariants()[0];

            $catalog->linkVariantToTaxon($product->productId->get(), $variant->variantId->get(), $taxon->taxonId->get());

            DB::table('trader_taxa_variants')
                ->where('variant_id', $variant->variantId->get())
                ->where('taxon_id', $taxon->taxonId->get())
                ->update(['data' => null]);

            $variantStates = $catalog->repos()->variantRepository()->getStatesByProduct($product->productId);
            $foundVariant = Variant::fromMappedData($variantStates[0][0], ['product_id' => $product->productId->get()], $variantStates[0][1]);
            $foundTaxonIds = array_map(fn ($variantTaxon) => $variantTaxon->taxonId->get(), $foundVariant->getVariantTaxa());

            $this->assertContains($taxon->taxonId->get(), $foundTaxonIds);
        }
    }
}
