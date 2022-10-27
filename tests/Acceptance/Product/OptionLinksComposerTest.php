<?php
declare(strict_types=1);

namespace Tests\Acceptance\Product;

use Tests\TestHelpers;
use Thinktomorrow\Trader\Application\Product\VariantLinks\VariantLinks;
use Thinktomorrow\Trader\Application\Product\VariantLinks\VariantLinksComposer;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryProductRepository;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;

class OptionLinksComposerTest extends ProductContext
{
    use TestHelpers;

    /** @test */
    public function it_can_compose_option_links()
    {
        $repo = new InMemoryProductRepository();

        $product = $this->createProductWithOptions();
        $repo->save($product);

        $optionLinks = (new VariantLinksComposer($repo, new TestContainer()))->get(
            $product->productId,
            $product->getVariants()[0]->variantId,
            Locale::fromString('nl', 'BE')
        );

        $this->assertCount(3, $optionLinks);
        $this->assertInstanceOf(VariantLinks::class, $optionLinks);
    }
}
