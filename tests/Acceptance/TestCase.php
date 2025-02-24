<?php
declare(strict_types=1);

namespace Tests\Acceptance;

use Illuminate\Support\Arr;
use Thinktomorrow\Trader\Application\Cart\PaymentMethod\UpdatePaymentMethodOnOrder;
use Thinktomorrow\Trader\Application\Cart\PaymentMethod\VerifyPaymentMethodForCart;
use Thinktomorrow\Trader\Application\Common\DataRenderer;
use Thinktomorrow\Trader\Application\Common\DefaultLocale;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountPriceDefaults;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\PaymentMethod\DefaultVerifyPaymentMethodForCart;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryOrderRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryPaymentMethodRepository;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;
use Thinktomorrow\Trader\Infrastructure\Test\TestTraderConfig;

class TestCase extends \PHPUnit\Framework\TestCase
{
    protected InMemoryOrderRepository $orderRepository;
    protected InMemoryPaymentMethodRepository $paymentMethodRepository;

    protected function setUp(): void
    {
        $this->addInstancesToContainer();

        DiscountPriceDefaults::setDiscountTaxRate(VatPercentage::fromString('21'));
        DiscountPriceDefaults::setDiscountIncludeTax(true);

        DefaultLocale::set(Locale::fromString('nl'));

        DataRenderer::setDataResolver(function (array $data, string $key, ?string $language = null, ?string $default = null) {
            if (! $language) {
                $language = 'nl';
            }

            $value = Arr::get(
                $data,
                $key . '.' . $language,
                Arr::get($data, $key, $default)
            );

            return $value === null ? $default : $value;
        });
    }

    private function addInstancesToContainer()
    {
        $this->orderRepository = new InMemoryOrderRepository();
        $this->paymentMethodRepository = new InMemoryPaymentMethodRepository();

        //        (new TestContainer())->add(VerifyPaymentMethodForCart::class, new DefaultVerifyPaymentMethodForCart());
        //        (new TestContainer())->add(UpdatePaymentMethodOnOrder::class, new UpdatePaymentMethodOnOrder(new TestContainer(), new TestTraderConfig(), $this->orderRepository, (new TestContainer())->get(VerifyPaymentMethodForCart::class), $this->paymentMethodRepository));
    }
}
