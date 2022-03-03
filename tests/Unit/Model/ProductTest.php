<?php
declare(strict_types=1);

namespace Tests\Unit\Model;

use Money\Money;
use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Common\Taxes\TaxRate;
use Thinktomorrow\Trader\Domain\Model\Product\Product;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\Option\Option;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\Variant;
use Thinktomorrow\Trader\Domain\Model\Product\Option\OptionId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Product\Event\OptionAdded;
use Thinktomorrow\Trader\Domain\Model\Product\Event\VariantAdded;
use Thinktomorrow\Trader\Domain\Model\Product\Event\ProductAdded;
use Thinktomorrow\Trader\Domain\Model\Product\Event\OptionUpdated;
use Thinktomorrow\Trader\Domain\Model\Product\Event\OptionDeleted;
use Thinktomorrow\Trader\Domain\Model\Product\Event\VariantUpdated;
use Thinktomorrow\Trader\Domain\Model\Product\Option\OptionValueId;
use Thinktomorrow\Trader\Domain\Model\Product\Event\VariantDeleted;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantUnitPrice;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantSalePrice;
use Thinktomorrow\Trader\Domain\Model\Product\Exceptions\CouldNotFindOptionOnProduct;
use Thinktomorrow\Trader\Domain\Model\Product\Exceptions\OptionAlreadyExistsOnProduct;

class ProductTest extends TestCase
{
    /** @test */
    public function it_can_create_a_product()
    {
        $product = $this->createdProduct();

        $this->assertEquals([
            'product_id' => 'xxx',
        ], $product->getMappedData());

        $this->assertEquals([
            new ProductAdded(ProductId::fromString('xxx')),
        ], $product->releaseEvents());
    }

    /** @test */
    public function it_can_be_build_from_raw_data()
    {
        $product = Product::fromMappedData([
            'product_id' => 'xxx',
        ]);

        $this->assertEquals(ProductId::fromString('xxx'), $product->getMappedData()['product_id']);
    }

    /** @test */
    public function it_can_add_option()
    {
        $product = $this->createdProduct();

        $product->addOption(Option::create($product->productId, OptionId::fromString('ooo')));

        $this->assertEquals([
            new ProductAdded(ProductId::fromString('xxx')),
            new OptionAdded(ProductId::fromString('xxx'), OptionId::fromString('ooo')),
        ], $product->releaseEvents());
    }

    /** @test */
    public function it_cannot_add_same_option_twice()
    {
        $this->expectException(OptionAlreadyExistsOnProduct::class);

        $product = $this->createdProduct();

        $product->addOption(Option::create($product->productId, OptionId::fromString('ooo')));
        $product->addOption(Option::create($product->productId, OptionId::fromString('ooo')));
    }

    /** @test */
    public function it_can_update_option()
    {
        $product = $this->createdProduct();

        $product->addOption($option = Option::create($product->productId, OptionId::fromString('ooo')));
        $product->updateOptionValueIds($option->optionId, [OptionValueId::fromString('ppp')]);

        $this->assertEquals([
            new ProductAdded(ProductId::fromString('xxx')),
            new OptionAdded(ProductId::fromString('xxx'), OptionId::fromString('ooo')),
            new OptionUpdated(ProductId::fromString('xxx'), OptionId::fromString('ooo')),
        ], $product->releaseEvents());
    }

    /** @test */
    public function it_cannot_update_option_that_is_not_present_on_product()
    {
        $this->expectException(CouldNotFindOptionOnProduct::class);

        $product = $this->createdProduct();
        $product->updateOptionValueIds(OptionId::fromString('ooo'), [OptionValueId::fromString('ppp')]);
    }

    /** @test */
    public function it_can_delete_option()
    {
        $product = $this->createdProduct();

        $product->addOption($option = Option::create($product->productId, OptionId::fromString('ooo')));
        $product->deleteOption($option->optionId);

        $this->assertEquals([
            new ProductAdded(ProductId::fromString('xxx')),
            new OptionAdded(ProductId::fromString('xxx'), OptionId::fromString('ooo')),
            new OptionDeleted(ProductId::fromString('xxx'), OptionId::fromString('ooo')),
        ], $product->releaseEvents());
    }

    /** @test */
    public function it_cannot_delete_option_that_is_not_present()
    {
        $this->expectException(CouldNotFindOptionOnProduct::class);

        $product = $this->createdProduct();
        $product->deleteOption(OptionId::fromString('ooo'));
    }

    /** @test */
    public function it_can_add_variant()
    {
        $product = $this->createdProductWithVariant();

        $this->assertEquals([
            new ProductAdded(ProductId::fromString('xxx')),
            new VariantAdded(ProductId::fromString('xxx'), VariantId::fromString('yyy')),
        ], $product->releaseEvents());
    }

    /** @test */
    public function it_cannot_add_variant_of_other_product()
    {
        $this->expectException(\InvalidArgumentException::class);

        $product = $this->createdProduct();

        $product->addVariant(Variant::create(
            ProductId::fromString('false-product-id'),
            VariantId::fromString('yyy'),
            VariantUnitPrice::zero(),
            VariantSalePrice::zero(),
        ));
    }


    /** @test */
    public function it_can_update_variant()
    {
        $product = $this->createdProductWithVariant();

        $product->addVariantOptionValue(
            VariantId::fromString('yyy'), OptionId::fromString('eee'), OptionValueId::fromString('aaa')
        );

        $product->updateVariantPrice(
            VariantId::fromString('yyy'), VariantUnitPrice::zero(), VariantSalePrice::zero()
        );

        $this->assertEquals('0', $product->getChildEntities()[Variant::class][0]['unit_price']);
        $this->assertEquals('0', $product->getChildEntities()[Variant::class][0]['sale_price']);
        $this->assertEquals([
            ['option_id' => 'eee', 'option_value_id' => 'aaa'],
        ], $product->getChildEntities()[Variant::class][0]['options']);
    }

    /** @test */
    public function it_can_delete_variant()
    {
        $product = $this->createdProductWithVariant();

        $product->deleteVariant(VariantId::fromString('yyy'));

        $this->assertEquals([
            new ProductAdded(ProductId::fromString('xxx')),
            new VariantAdded(ProductId::fromString('xxx'), VariantId::fromString('yyy')),
            new VariantDeleted(ProductId::fromString('xxx'), VariantId::fromString('yyy')),
        ], $product->releaseEvents());
    }
}
