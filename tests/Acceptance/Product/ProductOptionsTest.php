<?php
declare(strict_types=1);

namespace Tests\Acceptance\Product;

use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Model\Product\Option\OptionId;
use Thinktomorrow\Trader\Domain\Model\Product\Option\OptionValueId;
use Thinktomorrow\Trader\Application\Product\ProductOptions\ProductOption;
use Thinktomorrow\Trader\Application\Product\ProductOptions\ProductOptions;
use Thinktomorrow\Trader\Application\Product\ProductOptions\ProductOptionsComposer;
use function dd;

class ProductOptionsTest extends ProductContext
{
    /** @test */
    public function it_can_create_a_product_option()
    {
        $productOption = new ProductOption(OptionId::fromString('aaa'), OptionValueId::fromString('aaa-value'), []);

        $this->assertEquals(OptionId::fromString('aaa'), $productOption->optionId);
        $this->assertEquals(OptionValueId::fromString('aaa-value'), $productOption->optionValueId);
    }

    /** @test */
    public function it_can_render_product_option_data()
    {
        $productOption = new ProductOption(OptionId::fromString('aaa'), OptionValueId::fromString('aaa-value'), [
            'label' => 'color',
            'value' => 'aaa value',
        ]);

        $this->assertEquals('color', $productOption->getLabel());
        $this->assertEquals('aaa value', $productOption->getValue());
    }

    /** @test */
    public function it_can_render_product_option_localized_data()
    {
        $productOption = new ProductOption(OptionId::fromString('aaa'), OptionValueId::fromString('aaa-value'), [
            'label' => ['nl' => 'kleur', 'en' => 'color'],
            'value' => ['nl' => 'aaa waarde', 'en' => 'aaa value'],
        ]);

        // Default test locale is nl
        $this->assertEquals('kleur', $productOption->getLabel());
        $this->assertEquals('aaa waarde', $productOption->getValue());

        $productOption->setLocale(Locale::fromString('en', 'BE'));
        $this->assertEquals('color', $productOption->getLabel());
        $this->assertEquals('aaa value', $productOption->getValue());
    }

    /** @test */
    public function it_can_compose_options()
    {
        $composer = $this->productOptionsComposer->get();

        $productOptions = $this->createOptions();

        dd($productOptions);
    }

    private function createOptions()
    {
        $productOption = new ProductOption(OptionId::fromString('aaa'), OptionValueId::fromString('aaa-value'), [
            'label' => 'color',
            'value' => 'aaa value',
        ]);

        $productOption2 = new ProductOption(OptionId::fromString('bbb'), OptionValueId::fromString('bbb-value'), [
            'label' => 'size',
            'value' => 'bbb value',
        ]);

        return ProductOptions::fromType([
            $productOption, $productOption2,
        ]);
    }
}
