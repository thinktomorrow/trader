<?php
declare(strict_types=1);

namespace Tests\Acceptance\Product;

use Tests\TestHelpers;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;
use Thinktomorrow\Trader\Application\Product\OptionLinks\OptionLinks;
use Thinktomorrow\Trader\Application\Product\OptionLinks\OptionLinksComposer;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryProductRepository;

class OptionLinksComposerTest extends ProductContext
{
    use TestHelpers;

    /** @test */
    public function it_can_compose_option_links()
    {
        $repo = new InMemoryProductRepository();

        $product = $this->createdProductWithOptions();
        $repo->save($product);

        $optionLinks = (new OptionLinksComposer($repo, new TestContainer()))->get(
            $product->productId,
            $product->getVariants()[0]->variantId,
            Locale::fromString('nl', 'BE')
        );

        $this->assertCount(3, $optionLinks);
        $this->assertInstanceOf(OptionLinks::class, $optionLinks);
    }
}