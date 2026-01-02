<?php
declare(strict_types=1);

namespace Tests\Acceptance\Product;

use Tests\TestHelpers;
use Thinktomorrow\Trader\Domain\Model\Product\ProductTaxa\VariantProperty;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;

class MissingVariantsTest extends ProductContext
{
    use TestHelpers;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([$this->createTaxonomiesAndTaxa(), $this->createTaxonomiesAndTaxa(['ppp'], ['aaa'])] as [$taxonomies, $taxa]) {
            foreach ($taxonomies as $taxonomy) {
                $this->taxonomyRepository->save($taxonomy);
            }

            foreach ($taxa as $taxon) {
                $this->taxonRepository->save($taxon);
            }
        }
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_it_can_check_missing_variants()
    {
        $product = $this->createProductWithProductVariantProperties();
        $product->updateProductTaxa([
            VariantProperty::create($product->productId, TaxonId::fromString('xxx')),
            VariantProperty::create($product->productId, TaxonId::fromString('yyy')),
            VariantProperty::create($product->productId, TaxonId::fromString('zzz')),
            VariantProperty::create($product->productId, TaxonId::fromString('aaa')),
        ]);
        $this->catalogContext->catalogRepos()->productRepository()->save($product);

        $missingCombos = $this->missingOptionCombinations->get($product);

        $this->assertCount(3, $missingCombos);
        $this->assertEquals(['xxx', 'aaa'], $missingCombos[0]);
        $this->assertEquals(['yyy', 'aaa'], $missingCombos[1]);
        $this->assertEquals(['zzz', 'aaa'], $missingCombos[2]);
    }

    public function test_it_leaves_out_existing_variants()
    {
        $product = $this->createProductWithProductVariantProperties();
        $product->updateProductTaxa([
            VariantProperty::create($product->productId, TaxonId::fromString('xxx')),
            VariantProperty::create($product->productId, TaxonId::fromString('yyy')),
            VariantProperty::create($product->productId, TaxonId::fromString('zzz')),
            VariantProperty::create($product->productId, TaxonId::fromString('aaa')),
        ]);

        $variant = $product->getVariants()[0];

        $variant->updateVariantProperties([
            \Thinktomorrow\Trader\Domain\Model\Product\VariantTaxa\VariantProperty::create($variant->variantId, TaxonId::fromString('xxx')),
            \Thinktomorrow\Trader\Domain\Model\Product\VariantTaxa\VariantProperty::create($variant->variantId, TaxonId::fromString('aaa')),
        ]);

        $this->catalogContext->catalogRepos()->productRepository()->save($product);

        $missingCombos = $this->missingOptionCombinations->get($product);

        $this->assertCount(2, $missingCombos);
        $this->assertEquals(['yyy', 'aaa'], $missingCombos[0]);
        $this->assertEquals(['zzz', 'aaa'], $missingCombos[1]);
    }

    public function test_it_can_render_missing_combos_with_labels()
    {
        $product = $this->createProductWithProductVariantProperties();

        $prop1 = VariantProperty::create($product->productId, TaxonId::fromString('xxx'));
        $prop2 = VariantProperty::create($product->productId, TaxonId::fromString('yyy'));
        $prop3 = VariantProperty::create($product->productId, TaxonId::fromString('zzz'));
        $prop4 = VariantProperty::create($product->productId, TaxonId::fromString('aaa'));

        $prop1->addData(['title' => ['nl' => 'xxx value nl']]);
        $prop2->addData(['title' => ['nl' => 'yyy value nl']]);
        $prop3->addData(['title' => ['nl' => 'zzz value nl']]);
        $prop4->addData(['title' => ['nl' => 'aaa value nl']]);

        $product->updateProductTaxa([
            $prop1,
            $prop2,
            $prop3,
            $prop4,
        ]);


        $variant = $product->getVariants()[0];

        $variant->updateVariantProperties([
            \Thinktomorrow\Trader\Domain\Model\Product\VariantTaxa\VariantProperty::create($variant->variantId, TaxonId::fromString('xxx')),
            \Thinktomorrow\Trader\Domain\Model\Product\VariantTaxa\VariantProperty::create($variant->variantId, TaxonId::fromString('aaa')),
        ]);

        $this->catalogContext->catalogRepos()->productRepository()->save($product);

        $missingComboLabels = $this->missingOptionCombinations->getAsLabels($product, 'title.nl', 'title.nl');

        $this->assertCount(2, $missingComboLabels);
        $this->assertEquals([
            'Taxonomy qqq nl: Taxon yyy nl',
            'Taxonomy ppp nl: Taxon aaa nl',
        ], $missingComboLabels[0]);
    }
}
