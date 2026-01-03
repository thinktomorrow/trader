<?php
declare(strict_types=1);

namespace Tests\Acceptance\Product;

use Tests\TestHelpers;
use Thinktomorrow\Trader\Application\Product\VariantProperties\MissingVariants;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyType;

class MissingVariantsTest extends ProductContext
{
    use TestHelpers;

    private MissingVariants $missingOptionCombinations;

    protected function setUp(): void
    {
        parent::setUp();

        $this->missingOptionCombinations = new MissingVariants(
            $this->catalogContext->catalogRepos()->taxonomyRepository(),
            $this->catalogContext->catalogRepos()->taxonRepository(),
        );
    }

    public function test_it_can_check_missing_variants()
    {
        $this->catalogContext->createTaxonomy('taxonomy-aaa', TaxonomyType::variant_property->value);
        $taxonomy2 = $this->catalogContext->createTaxonomy('taxonomy-bbb', TaxonomyType::variant_property->value);
        $taxon = $this->catalogContext->createTaxon();
        $taxon2 = $this->catalogContext->createTaxon('taxon-bbb');
        $taxon3 = $this->catalogContext->createTaxon('taxon-ccc', $taxonomy2->taxonomyId->get());

        $product = $this->catalogContext->createProduct();
        $variantId = $product->getVariants()[0]->variantId;

        $this->catalogContext->linkProductToTaxon($product->productId->get(), $taxon->taxonId->get());
        $this->catalogContext->linkProductToTaxon($product->productId->get(), $taxon2->taxonId->get());
        $this->catalogContext->linkProductToTaxon($product->productId->get(), $taxon3->taxonId->get());


        $missingCombos = $this->missingOptionCombinations->get($product);

        $this->assertCount(2, $missingCombos);
        $this->assertEquals(['taxon-aaa', 'taxon-ccc'], $missingCombos[0]);
        $this->assertEquals(['taxon-bbb', 'taxon-ccc'], $missingCombos[1]);
    }

    public function test_it_leaves_out_existing_variants()
    {
        $this->catalogContext->createTaxonomy('taxonomy-aaa', TaxonomyType::variant_property->value);
        $taxonomy2 = $this->catalogContext->createTaxonomy('taxonomy-bbb', TaxonomyType::variant_property->value);
        $taxon = $this->catalogContext->createTaxon();
        $taxon2 = $this->catalogContext->createTaxon('taxon-bbb');
        $taxon3 = $this->catalogContext->createTaxon('taxon-ccc', $taxonomy2->taxonomyId->get());

        $product = $this->catalogContext->createProduct();
        $variantId = $product->getVariants()[0]->variantId;

        $this->catalogContext->linkProductToTaxon($product->productId->get(), $taxon->taxonId->get());
        $this->catalogContext->linkProductToTaxon($product->productId->get(), $taxon2->taxonId->get());
        $this->catalogContext->linkProductToTaxon($product->productId->get(), $taxon3->taxonId->get());
        $this->catalogContext->linkVariantToTaxon($product->productId->get(), $variantId->get(), $taxon->taxonId->get());
        $this->catalogContext->linkVariantToTaxon($product->productId->get(), $variantId->get(), $taxon3->taxonId->get());

        $missingCombos = $this->missingOptionCombinations->get($product);

        $this->assertCount(1, $missingCombos);
        $this->assertEquals(['taxon-bbb', 'taxon-ccc'], $missingCombos[0]);
    }

    public function test_it_can_render_missing_combos_with_labels()
    {
        $this->catalogContext->createTaxonomy('taxonomy-aaa', TaxonomyType::variant_property->value);
        $taxonomy2 = $this->catalogContext->createTaxonomy('taxonomy-bbb', TaxonomyType::variant_property->value);
        $taxon = $this->catalogContext->createTaxon();
        $taxon2 = $this->catalogContext->createTaxon('taxon-bbb');
        $taxon3 = $this->catalogContext->createTaxon('taxon-ccc', $taxonomy2->taxonomyId->get());

        $product = $this->catalogContext->createProduct();
        $variantId = $product->getVariants()[0]->variantId;

        $this->catalogContext->linkProductToTaxon($product->productId->get(), $taxon->taxonId->get());
        $this->catalogContext->linkProductToTaxon($product->productId->get(), $taxon2->taxonId->get());
        $this->catalogContext->linkProductToTaxon($product->productId->get(), $taxon3->taxonId->get());

        $missingComboLabels = $this->missingOptionCombinations->getAsLabels($product, 'title.nl', 'title.nl');

        $this->assertCount(2, $missingComboLabels);
        $this->assertEquals([
            'taxonomy-aaa title nl: taxon-aaa title nl',
            'taxonomy-bbb title nl: taxon-ccc title nl',
        ], $missingComboLabels[0]);

        $this->assertEquals([
            'taxonomy-aaa title nl: taxon-bbb title nl',
            'taxonomy-bbb title nl: taxon-ccc title nl',
        ], $missingComboLabels[1]);
    }
}
