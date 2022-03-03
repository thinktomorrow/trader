<?php
declare(strict_types=1);

namespace Tests\Acceptance;

use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Application\Common\DataRenderer;
use Thinktomorrow\Trader\Domain\Model\Product\Option\OptionId;
use Thinktomorrow\Trader\Domain\Model\Product\Option\OptionValueId;
use Thinktomorrow\Trader\Application\Product\ProductOptions\ProductOption;
use Thinktomorrow\Trader\Application\Product\ProductOptions\ProductOptions;
use Thinktomorrow\Trader\Application\Product\ProductOptions\ProductOptionsComposer;

class ProductOptionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DataRenderer::setResolver(function(array $data, string $key, string $language = null, string $default = null)
        {
            if(!isset($data[$key])) {
                return $default;
            }

            if(!$language) {
                $language = 'nl';
            }

            if(isset($data[$key][$language])) {
                return $data[$key][$language];
            }

            return $data[$key];
        });
    }

    /** @test */
    public function it_can_render_data()
    {
        $productOption = new ProductOption(OptionId::fromString('aaa'), OptionValueId::fromString('aaa-value'), [
            'label' => 'color',
            'value' => 'aaa value',
        ]);

        $this->assertEquals(OptionId::fromString('aaa'), $productOption->optionId);
        $this->assertEquals(OptionValueId::fromString('aaa-value'), $productOption->optionValueId);
        $this->assertEquals('color', $productOption->getLabel());
        $this->assertEquals('aaa value', $productOption->getValue());
    }

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
