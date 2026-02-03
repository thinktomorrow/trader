<?php
declare(strict_types=1);

namespace Tests\Unit\Model\Product;

use Money\Money;
use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;
use Thinktomorrow\Trader\Domain\Model\Product\Exceptions\InvalidVariantIdOnVariantKey;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\Variant;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantSalePrice;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantUnitPrice;
use Thinktomorrow\Trader\Domain\Model\Product\VariantKey\VariantKey;
use Thinktomorrow\Trader\Domain\Model\Product\VariantKey\VariantKeyId;

class VariantKeyTest extends TestCase
{
    public function test_it_can_add_variant_key()
    {
        $variant = $this->createdVariant();

        $variant->updateVariantKeys([
            $variantKey = VariantKey::create($variant->variantId, VariantKeyId::fromString('xxx'), Locale::fromString('nl_BE')),
        ]);

        $this->assertEquals([$variantKey], $variant->getVariantKeys());
    }

    public function test_it_can_update_variant_key()
    {
        $variant = $this->createdVariant();

        $variant->updateVariantKeys([
            $variantKey = VariantKey::create($variant->variantId, VariantKeyId::fromString('xxx'), Locale::fromString('nl_BE')),
        ]);

        $variant->updateVariantKeys([
            $variantKeyUpdated = VariantKey::create($variant->variantId, VariantKeyId::fromString('yyy'), Locale::fromString('nl_BE')),
        ]);

        $this->assertEquals([$variantKeyUpdated], $variant->getVariantKeys());

        // TODO: test this from the product aggregate level?
        //        $this->assertEquals([new VariantKeyUpdated($variant->variantId, Locale::fromString('nl_BE'), VariantKeyId::fromString('xxx'), VariantKeyId::fromString('yyy'))], $variant->releaseEvents());
    }

    public function test_it_protects_against_invalid_variant_id_on_variant_key()
    {
        $this->expectException(InvalidVariantIdOnVariantKey::class);

        $variant = $this->createdVariant();

        $variant->updateVariantKeys([
            VariantKey::create(VariantId::fromString('invalid'), VariantKeyId::fromString('xxx'), Locale::fromString('nl_BE')),
        ]);

        $this->assertEquals([], $variant->getVariantKeys());
    }

    public function test_variant_key_is_per_locale()
    {
        $variant = $this->createdVariant();

        $variant->updateVariantKeys([
            $variantKey = VariantKey::create($variant->variantId, VariantKeyId::fromString('xxx'), Locale::fromString('nl_BE')),
            $variantKey2 = VariantKey::create($variant->variantId, VariantKeyId::fromString('xxx-fr'), Locale::fromString('fr_BE')),
        ]);

        // Override by locale
        $variant->updateVariantKeys([
            $variantKey3 = VariantKey::create($variant->variantId, VariantKeyId::fromString('yyy'), Locale::fromString('nl_BE')),
        ]);

        $this->assertEquals([$variantKey3, $variantKey2], $variant->getVariantKeys());
    }

    public function test_it_can_check_if_variant_has_variant_key_id()
    {
        $variant = $this->createdVariant();

        $variant->updateVariantKeys([
            $variantKey = VariantKey::create($variant->variantId, VariantKeyId::fromString('xxx'), Locale::fromString('nl_BE')),
            $variantKey2 = VariantKey::create($variant->variantId, VariantKeyId::fromString('xxx-fr'), Locale::fromString('fr_BE')),
        ]);

        $this->assertTrue($variant->hasVariantKeyId($variantKey->getKey()));
        $this->assertTrue($variant->hasVariantKeyId($variantKey2->getKey()));
        $this->assertFalse($variant->hasVariantKeyId(VariantKeyId::fromString('invalid')));
    }

    private function createdVariant(): Variant
    {
        return Variant::create(
            ProductId::fromString('xxx'),
            VariantId::fromString('yyy'),
            VariantUnitPrice::fromMoney(
                Money::EUR(10),
                VatPercentage::fromString('20'),
                false
            ),
            VariantSalePrice::fromMoney(Money::EUR(8), VatPercentage::fromString('20'), false),
            'sku',
        );
    }
}
