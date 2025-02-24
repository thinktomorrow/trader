<?php

namespace Tests\Infrastructure\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Application\Cart\PaymentMethod\PaymentMethodForCart;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\PaymentMethod\DefaultPaymentMethodForCart;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlCountryRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlPaymentMethodRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryCountryRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryPaymentMethodRepository;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;

class PaymentMethodForCartRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private \Thinktomorrow\Trader\Domain\Model\Country\Country $country;

    protected function setUp(): void
    {
        parent::setUp();

        $this->country = $this->createCountry(['country_id' => 'BE']);

        (new TestContainer())->add(PaymentMethodForCart::class, DefaultPaymentMethodForCart::class);
    }

    public function test_it_can_find_payment_methods_for_cart()
    {
        $paymentMethod = $this->createPaymentMethod();

        foreach ($this->repositories() as $i => $repository) {
            $this->countryRepositories()[$i]->save($this->country);

            $this->paymentMethodRepositories()[$i]->save($paymentMethod);

            $this->assertCount(1, $repository->findAllPaymentMethodsForCart());
        }
    }

    public function test_it_can_find_methods_for_cart_with_matching_countries()
    {
        $paymentMethod = $this->createPaymentMethod();
        $paymentMethod->addCountry(CountryId::fromString('BE'));

        foreach ($this->repositories() as $i => $repository) {
            $this->countryRepositories()[$i]->save($this->country);

            $this->paymentMethodRepositories()[$i]->save($paymentMethod);

            $this->assertCount(1, $repository->findAllPaymentMethodsForCart('BE'));
            $this->assertCount(0, $repository->findAllPaymentMethodsForCart('NL'));
        }
    }

    private function repositories(): \Generator
    {
        yield new InMemoryPaymentMethodRepository();
        yield new MysqlPaymentMethodRepository(new TestContainer());
    }

    private function paymentMethodRepositories(): array
    {
        return [
            new InMemoryPaymentMethodRepository(),
            new MysqlPaymentMethodRepository(new TestContainer()),
        ];
    }

    private function countryRepositories(): array
    {
        return [
            new InMemoryCountryRepository(),
            new MysqlCountryRepository(),
        ];
    }
}
