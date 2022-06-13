<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\ShippingProfile;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Entity\ChildEntity;

final class Tariff implements ChildEntity
{
    public readonly ShippingProfileId $shippingProfileId;
    public readonly TariffNumber $tariffNumber;
    private Money $rate;
    private Money $from;
    private Money $to;

    private function __construct()
    {
    }

    public static function create(ShippingProfileId $shippingProfileId, TariffNumber $tariffNumber, Money $rate, Money $from, Money $to): static
    {
        $object = new static();

        $object->shippingProfileId = $shippingProfileId;
        $object->tariffNumber = $tariffNumber;
        $object->rate = $rate;
        $object->from = $from;
        $object->to = $to;

        return $object;
    }

    public function withinRange(Money $amount): bool
    {
        return $this->from->lessThanOrEqual($amount) && $this->to->greaterThanOrEqual($amount);
    }

    public function getRate(): Money
    {
        return $this->rate;
    }

    public function update(Money $rate, Money $from, Money $to): void
    {
        $this->rate = $rate;
        $this->from = $from;
        $this->to = $to;
    }

    public function getMappedData(): array
    {
        return [
            'shipping_profile_id' => $this->shippingProfileId->get(),
            'tariff_number' => $this->tariffNumber->asInt(),
            'rate' => $this->rate->getAmount(),
            'from' => $this->from->getAmount(),
            'to' => $this->to->getAmount(),
        ];
    }

    public static function fromMappedData(array $state, array $aggregateState): static
    {
        $tariff = new static();

        $tariff->shippingProfileId = ShippingProfileId::fromString($aggregateState['shipping_profile_id']);
        $tariff->tariffNumber = TariffNumber::fromInt($state['tariff_number']);
        $tariff->rate = Money::EUR($state['rate']);
        $tariff->from = Money::EUR($state['from']);
        $tariff->to = Money::EUR($state['to']);

        return $tariff;
    }
}
