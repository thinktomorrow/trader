<?php
declare(strict_types=1);

namespace Tests\Unit\Model\Product;

use Money\Money;
use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\Variant;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantSalePrice;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantUnitPrice;
use Thinktomorrow\Trader\Domain\Model\Product\VariantTaxa\VariantTaxon;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyId;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyType;

class VariantPropertyTest extends TestCase
{
    public function test_a_variant_can_have_variant_properties()
    {
        $variant = Variant::create(
            $productId = ProductId::fromString('xxx'),
            $variantId = VariantId::fromString('yyy'),
            $productUnitPrice = VariantUnitPrice::fromMoney(
                Money::EUR(10),
                VatPercentage::fromString('20'),
                false
            ),
            $productSalePrice = VariantSalePrice::fromMoney(Money::EUR(8), VatPercentage::fromString('20'), false),
            'sku',
        );

        $variantProperty = VariantTaxon::create(
            $variantId,
            $taxonomyId = TaxonomyId::fromString('aaa'),
            $taxonomyType = TaxonomyType::variant_property,
            $taxonId = TaxonId::fromString('bbb')
        );

        $variant->updateVariantTaxa([
            $variantProperty,
        ]);

        $this->assertEquals([
            $variantProperty,
        ], $variant->getVariantTaxa());
    }
}
