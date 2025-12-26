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

//    protected InMemoryOrderRepository $orderRepository;
//    protected InMemoryPaymentMethodRepository $paymentMethodRepository;

    protected function setUp(): void
    {
//        $this->addInstancesToContainer();

        DefaultLocale::set(Locale::fromString('nl'));

        DataRenderer::setDataResolver(function (array $data, string $key, ?string $language = null, ?string $default = null) {
            if (!$language) {
                $language = 'nl';
            }

            $value = Arr::get(
                $data,
                $key . '.' . $language,
                Arr::get($data, $key, $default)
            );

            return $value === null ? $default : $value;
        });

        CatalogContext::setUp();
        OrderContext::setUp();

        $this->catalogContext = CatalogContext::inMemory();
        $this->orderContext = OrderContext::inMemory();
    }

    protected function tearDown(): void
    {
        CatalogContext::tearDown();
        OrderContext::tearDown();
    }

    private function addInstancesToContainer()
    {
//        $this->orderRepository = new InMemoryOrderRepository();
//        $this->paymentMethodRepository = new InMemoryPaymentMethodRepository();

        //        (new TestContainer())->add(VerifyPaymentMethodForCart::class, new DefaultVerifyPaymentMethodForCart());
        //        (new TestContainer())->add(UpdatePaymentMethodOnOrder::class, new UpdatePaymentMethodOnOrder(new TestContainer(), new TestTraderConfig(), $this->orderRepository, (new TestContainer())->get(VerifyPaymentMethodForCart::class), $this->paymentMethodRepository));
    }
}
