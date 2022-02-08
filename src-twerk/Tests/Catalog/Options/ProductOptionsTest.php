<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Tests\Catalog\Options;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use Thinktomorrow\Trader\Catalog\Products\Domain\ProductGroupRepository;

class ProductOptionsTest extends TestCase
{
    use DatabaseMigrations;
    use OptionTestHelpers;

    /** @test */
    public function it_can_get_the_product_options()
    {
        $product = $this->productWithOption();

        $this->assertCount(1, $product->getOptions());
        $this->assertEquals('blue', $product->getOptions()[0]->getValue('nl'));
    }

    /** @test */
    public function it_can_check_the_product_options()
    {
        $product = $this->productWithOption();

        $this->assertTrue($product->hasOption('1'));
        $this->assertFalse($product->hasOption('2'));
    }

    /** @test */
    public function it_can_get_the_available_productgroup_options()
    {
        $productGroup = $this->createProductGroup();
        $this->defaultOptionSetup($productGroup->getId());

        $productGroup = app(ProductGroupRepository::class)->findById($productGroup->getId());

        $this->assertCount(3, $productGroup->getOptions());
        $this->assertEquals('blauw', $productGroup->getOptions()[0]->getValue('nl'));
        $this->assertEquals('blue', $productGroup->getOptions()[0]->getValue('en'));
    }

    /** @test */
    public function it_can_get_the_available_productgroup_options_grouped()
    {
        $productGroup = $this->createProductGroup();
        $this->defaultOptionSetup($productGroup->getId());

        $productGroup = app(ProductGroupRepository::class)->findById($productGroup->getId());

        $this->assertCount(2, $productGroup->getOptions()->grouped());
        $this->assertEquals('blauw', $productGroup->getOptions()->grouped()[0][0]->getValue('nl'));
        $this->assertEquals('groen', $productGroup->getOptions()->grouped()[0][1]->getValue('nl'));
        $this->assertEquals('melk', $productGroup->getOptions()->grouped()[1][0]->getValue('nl'));
    }

    /** @test */
    public function it_can_get_the_option_label()
    {
        $productGroup = $this->createProductGroup();
        $this->defaultOptionSetup($productGroup->getId());

        $productGroup = app(ProductGroupRepository::class)->findById($productGroup->getId());

        $this->assertEquals('kleur', $productGroup->getOptions()->grouped()[0][0]->getLabel('nl'));
        $this->assertEquals('color', $productGroup->getOptions()->grouped()[0][0]->getLabel('en'));
    }
}
