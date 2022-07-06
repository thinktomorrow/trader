<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\ShippingProfile;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Entity\ChildEntity;

final class Tariff implements ChildEntity
{
    public readonly TariffId $tariffId;
    public readonly ShippingProfileId $shippingProfileId;
    private Money $rate;
    private Money $from;
    private ?Money $to;

    private function __construct()
    {
    }

    public static function create(TariffId $tariffId, ShippingProfileId $shippingProfileId, Money $rate, Money $from, ?Money $to): static
    {
        $object = new static();

        $object->tariffId = $tariffId;
        $object->shippingProfileId = $shippingProfileId;
        $object->rate = $rate;
        $object->from = $from;
        $object->to = $to;

        return $object;
    }

    public function withinRange(Money $amount): bool
    {
        return $this->from->lessThanOrEqual($amount) && (is_null($this->to) || $this->to->greaterThanOrEqual($amount));
    }

    public function getRate(): Money
    {
        return $this->rate;
    }

    public function update(Money $rate, Money $from, ?Money $to): void
    {
        $this->rate = $rate;
        $this->from = $from;
        $this->to = $to;
    }

    public function getMappedData(): array
    {
        return [
            'tariff_id' => $this->tariffId->get(),
            'shipping_profile_id' => $this->shippingProfileId->get(),
            'rate' => $this->rate->getAmount(),
            'from' => $this->from->getAmount(),
            'to' => $this->to ? $this->to->getAmount() : null,
        ];
    }

    public static function fromMappedData(array $state, array $aggregateState): static
    {
        $tariff = new static();

        $tariff->tariffId = TariffId::fromString($state['tariff_id']);
        $tariff->shippingProfileId = ShippingProfileId::fromString($aggregateState['shipping_profile_id']);
        $tariff->rate = Cash::make($state['rate']);
        $tariff->from = Cash::make($state['from']);
        $tariff->to = $state['to'] ? Cash::make($state['to']) : null;

        return $tariff;
    }

    public function equals($other): bool
    {
        return get_class($other) === get_class($this)
            && $this->tariffId === $other->tariffId;
    }
}
