<?php
declare(strict_types=1);

namespace Tests\Acceptance\Product;

use Tests\TestHelpers;
use Thinktomorrow\Trader\Application\Product\VariantLinks\VariantLinks;
use Thinktomorrow\Trader\Application\Product\VariantLinks\VariantLinksComposer;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;

class VariantLinksTest extends ProductContext
{
    use TestHelpers;

    public function test_it_can_compose_variant_links()
    {
        $product = $this->createProductWithProductVariantProperties();
        $this->productRepository->save($product);

        $links = (new VariantLinksComposer($this->productRepository, new TestContainer()))->get(
            $product->productId,
            $product->getVariants()[0]->variantId,
            Locale::fromString('nl', 'BE')
        );

        $this->assertCount(3, $links);
        $this->assertInstanceOf(VariantLinks::class, $links);
    }

    public function test_it_can_compose_variant_links_per_locale()
    {
        $this->markTestIncomplete();
        
        $product = $this->createProductWithProductVariantProperties();
        $this->productRepository->save($product);

        $links = (new VariantLinksComposer($this->productRepository, new TestContainer()))->get(
            $product->productId,
            $product->getVariants()[0]->variantId,
            Locale::fromString('nl', 'BE')
        );

        $this->assertEquals('/yyy', $links[0]->getUrl());
        $this->assertEquals('/yyy', $links[1]->getUrl());
        $this->assertEquals('/yyy', $links[2]->getUrl());
    }
}
