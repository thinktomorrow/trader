<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Thinktomorrow\Trader\Application\Promo\OrderPromo\OrderConditionFactory;
use Thinktomorrow\Trader\Application\Promo\OrderPromo\OrderDiscountFactory;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\Promo\ConditionFactory;
use Thinktomorrow\Trader\Domain\Model\Promo\DiscountFactory;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlCountryRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlCustomerRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlPaymentMethodRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlProductRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlPromoRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlShippingProfileRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlVariantRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryCountryRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryCustomerRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryPaymentMethodRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryProductRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryPromoRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryShippingProfileRepository;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;

trait PrepareWorld
{
    private function prepareWorldForOrder($i)
    {
        $this->prepareCountries($i);
        $this->prepareCustomer($i);

        $productRepository = iterator_to_array($this->productRepositories())[$i];
        $product = $this->createProductWithPersonalisations();
        $productRepository->save($product);

        $promoRepository = iterator_to_array($this->promoRepositories())[$i];
        $promo = $this->createPromo([], [
            $this->createDiscount(['discount_id' => 'ddd'], []),
        ]);
        $promoRepository->save($promo);

        $promo = $this->createPromo(['promo_id' => 'def'], [
            $this->createDiscount(['discount_id' => 'eee'], []),
        ]);
        $promoRepository->save($promo);

        $shippingProfileRepository = iterator_to_array($this->shippingProfileRepositories())[$i];
        $shippingProfile = $this->createShippingProfile();
        $shippingProfileRepository->save($shippingProfile);

        $paymentMethodRepository = iterator_to_array($this->paymentMethodRepositories())[$i];
        $paymentMethod = $this->createPaymentMethod();
        $paymentMethodRepository->save($paymentMethod);
    }

    private function destroyWorldForOrder($i)
    {
        $countryRepository = iterator_to_array($this->countryRepositories())[$i];
        $countryRepository->delete(CountryId::fromString('BE'));
        $countryRepository->delete(CountryId::fromString('NL'));

        $customerRepository = iterator_to_array($this->customerRepositories())[$i];
        $customerRepository->delete($this->createCustomer()->customerId);

        $productRepository = iterator_to_array($this->productRepositories())[$i];
        $product = $this->createProductWithPersonalisations();
        $productRepository->delete($product->productId);

        $promoRepository = iterator_to_array($this->promoRepositories())[$i];
        $promo = $this->createPromo([], [
            $this->createDiscount(['discount_id' => 'ddd'], []),
        ]);
        $promoRepository->delete($promo->promoId);

        $promo = $this->createPromo(['promo_id' => 'def'], [
            $this->createDiscount(['discount_id' => 'eee'], []),
        ]);
        $promoRepository->delete($promo->promoId);

        $shippingProfileRepository = iterator_to_array($this->shippingProfileRepositories())[$i];
        $shippingProfile = $this->createShippingProfile();
        $shippingProfileRepository->delete($shippingProfile->shippingProfileId);

        $paymentMethodRepository = iterator_to_array($this->paymentMethodRepositories())[$i];
        $paymentMethod = $this->createPaymentMethod();
        $paymentMethodRepository->delete($paymentMethod->paymentMethodId);
    }

    private function prepareCountries($i)
    {
        $countryRepository = iterator_to_array($this->countryRepositories())[$i];
        $country = $this->createCountry(['country_id' => 'BE']);
        $countryRepository->save($country);
        $country = $this->createCountry(['country_id' => 'NL']);
        $countryRepository->save($country);
    }

    private function prepareCustomer($i)
    {
        $customerRepository = iterator_to_array($this->customerRepositories())[$i];
        $customer = $this->createCustomer();
        $customerRepository->save($customer);
    }

    private function countryRepositories(): \Generator
    {
        yield new InMemoryCountryRepository();
        yield new MysqlCountryRepository();
    }

    private function customerRepositories(): \Generator
    {
        yield new InMemoryCustomerRepository();
        yield new MysqlCustomerRepository(new TestContainer());
    }

    private function productRepositories(): \Generator
    {
        yield new InMemoryProductRepository();
        yield new MysqlProductRepository(new MysqlVariantRepository(new TestContainer()));
    }

    private function shippingProfileRepositories(): \Generator
    {
        yield new InMemoryShippingProfileRepository();
        yield new MysqlShippingProfileRepository(new TestContainer());
    }

    private function paymentMethodRepositories(): \Generator
    {
        yield new InMemoryPaymentMethodRepository();
        yield new MysqlPaymentMethodRepository();
    }

    private function promoRepositories(): \Generator
    {
        $factories = [
            new DiscountFactory([], new ConditionFactory([])),
            new OrderDiscountFactory([], new OrderConditionFactory([])),
        ];

        yield new InMemoryPromoRepository(...$factories);
        yield new MysqlPromoRepository(...$factories);
    }
}
