<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Test;

use Thinktomorrow\Trader\Domain\Model\Shipping\ShippingId;
use Thinktomorrow\Trader\Domain\Model\Order\Price\SubTotal;
use Thinktomorrow\Trader\Domain\Model\Shipping\Entity\Rule;
use Thinktomorrow\Trader\Domain\Model\Shipping\ShippingState;
use Thinktomorrow\Trader\Domain\Model\Shipping\Entity\Shipping;
use Thinktomorrow\Trader\Domain\Model\Order\Details\Shipping as ShippingRead;
use Thinktomorrow\Trader\Domain\Model\Shipping\ShippingCountry;
use Thinktomorrow\Trader\Domain\Model\Shipping\Entity\ShippingRepository;
use Thinktomorrow\Trader\Domain\Model\Shipping\Exceptions\CouldNotFindShipping;
use Thinktomorrow\Trader\Application\Cart\Adjusters\MatchingShippingRepository;

final class InMemoryShippingRepository implements ShippingRepository, MatchingShippingRepository
{
    private static array $shippings = [];

    private string $nextReference = 'sss-123';

    public function save(Shipping $shipping): void
    {
        static::$shippings[$shipping->shippingId->get()] = $shipping;
    }

    public function find(ShippingId $shippingId): Shipping
    {
        if(!isset(static::$shippings[$shippingId->get()])) {
            throw new CouldNotFindShipping('No shipping found by id ' . $shippingId);
        }

        return static::$shippings[$shippingId->get()];
    }

    public function findMatch(ShippingId $shippingId, SubTotal $subTotal, ShippingCountry $country, \DateTimeImmutable $date): ShippingRead
    {
        if(isset(static::$shippings[$shippingId->get()])) {
            $shipping = static::$shippings[$shippingId->get()];

            // FOr testing we just return the first rule as a match
            /** @var Rule $rule */
            foreach($shipping->getChildEntities()[Rule::class] as $rule) {
                return ShippingRead::fromMappedData([
                    'id' => $shippingId->get(),
                    'state' => ShippingState::initialized->value,
                    'cost' => $rule->getCost()->getMoney()->getAmount(),
                    'tax_rate' => $rule->getCost()->getTaxRate()->toPercentage()->get(),
                    'includes_vat' => $rule->getCost()->includesTax(),
                ], []);
            }
        }

        throw new CouldNotFindShipping();
    }

    public function delete(ShippingId $shippingId): void
    {
        if(!isset(static::$shippings[$shippingId->get()])) {
            throw new CouldNotFindShipping('No available shipping found by id ' . $shippingId);
        }

        unset(static::$shippings[$shippingId->get()]);
    }

    public function nextReference(): ShippingId
    {
        return ShippingId::fromString($this->nextReference);
    }

    // For testing purposes only
    public function setNextReference(string $nextReference): void
    {
        $this->nextReference = $nextReference;
    }

    public function clear()
    {
        static::$shippings = [];
    }
}
