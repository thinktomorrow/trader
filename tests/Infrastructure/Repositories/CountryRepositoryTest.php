<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\Country\Exceptions\CouldNotFindCountry;
use Thinktomorrow\Trader\Testing\Order\OrderContext;

class CountryRepositoryTest extends TestCase
{
    public function test_it_can_save_and_find_a_country()
    {
        foreach (OrderContext::drivers() as $orderContext) {
            $repository = $orderContext->repos()->countryRepository();

            $country = $orderContext->createCountry('BE');
            $country->releaseEvents();

            $this->assertEquals($country, $repository->find($country->countryId));
        }
    }

    public function test_it_can_delete_a_country()
    {
        $countriesNotFound = 0;

        foreach (OrderContext::drivers() as $orderContext) {

            $repository = $orderContext->repos()->countryRepository();

            $country = $orderContext->createCountry('BE');


            $repository->delete($country->countryId);

            try {
                $repository->find($country->countryId);
            } catch (CouldNotFindCountry $e) {
                $countriesNotFound++;
            }
        }

        $this->assertEquals(count(OrderContext::drivers()), $countriesNotFound);
    }

    public function test_it_can_get_available_billing_countries()
    {
        foreach (OrderContext::drivers() as $orderContext) {

            $repository = $orderContext->repos()->countryRepository();

            $country = $orderContext->createCountry('BE');
            $country2 = $orderContext->createCountry('NL');

            $this->assertEquals([
                \Thinktomorrow\Trader\Application\Country\Country::fromMappedData($country->getMappedData()),
                \Thinktomorrow\Trader\Application\Country\Country::fromMappedData($country2->getMappedData()),
            ], $repository->getAvailableBillingCountries());
        }
    }

    public function test_it_can_find_billing_country()
    {
        foreach (OrderContext::drivers() as $orderContext) {

            $repository = $orderContext->repos()->countryRepository();

            $country = $orderContext->createCountry('BE');

            $this->assertEquals(
                \Thinktomorrow\Trader\Application\Country\Country::fromMappedData($country->getMappedData()),
                $repository->findBillingCountry(CountryId::fromString('BE'))
            );
        }
    }
}
