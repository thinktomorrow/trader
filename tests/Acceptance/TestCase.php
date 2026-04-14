<?php

declare(strict_types=1);

namespace Tests\Acceptance;

use Illuminate\Support\Arr;
use Thinktomorrow\Trader\Application\Common\DataRenderer;
use Thinktomorrow\Trader\Application\Common\DefaultLocale;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Testing\Catalog\CatalogContext;
use Thinktomorrow\Trader\Testing\Order\OrderContext;

class TestCase extends \PHPUnit\Framework\TestCase
{
    protected CatalogContext $catalogContext;

    protected OrderContext $orderContext;

    protected function setUp(): void
    {
        DefaultLocale::set(Locale::fromString('nl'));

        DataRenderer::setDataResolver(function (array $data, string $key, ?string $language = null, $default = null) {
            if (! $language) {
                $language = 'nl';
            }

            $value = Arr::get(
                $data,
                $key.'.'.$language,
                Arr::get($data, $key, $default)
            );

            return $value === null ? $default : $value;
        });

        CatalogContext::setUp();
        OrderContext::setUp();

        $this->catalogContext = CatalogContext::inMemory();
        $this->orderContext = OrderContext::inMemory();

        $this->orderContext->createSalePriceSystemPromo();
    }

    protected function tearDown(): void
    {
        CatalogContext::tearDown();
        OrderContext::tearDown();
    }

    private function addInstancesToContainer()
    {
        //        $this->orderContext->repos()->orderRepository() = new InMemoryOrderRepository();
        //        $this->orderContext->repos()->paymentMethodRepository() = new InMemoryPaymentMethodRepository();

        //        (new TestContainer())->add(VerifyPaymentMethodForCart::class, new DefaultVerifyPaymentMethodForCart());
        //        (new TestContainer())->add(UpdatePaymentMethodOnOrder::class, new UpdatePaymentMethodOnOrder(new TestContainer(), new TestTraderConfig(), $this->orderContext->repos()->orderRepository(), (new TestContainer())->get(VerifyPaymentMethodForCart::class), $this->orderContext->repos()->paymentMethodRepository()));
    }
}
