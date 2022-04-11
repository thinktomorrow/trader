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

class ProductDataTest extends ProductContext
{


    /** @test */
    public function it_can_render_localized_data()
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
        $composer = new ProductOptionsComposer(
        // ProductOptionsRepo
        // VariantRepo
        );

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
