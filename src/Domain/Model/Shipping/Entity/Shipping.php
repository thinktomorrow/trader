<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Shipping\Entity;

use Assert\Assertion;
use Thinktomorrow\Trader\Domain\Common\Entity\Aggregate;
use Thinktomorrow\Trader\Domain\Model\Shipping\ShippingId;
use Thinktomorrow\Trader\Domain\Common\Event\RecordsEvents;

final class Shipping implements Aggregate
{
    use RecordsEvents;

    public readonly ShippingId $shippingId;
    private array $rules;

    public static function create(ShippingId $shippingId, array $rules): static
    {
        Assertion::allIsInstanceOf($rules, Rule::class);

        $shipping = new static();
        $shipping->shippingId = $shippingId;
        $shipping->rules = $rules;

        return $shipping;
    }

    public function getMappedData(): array
    {
        return [
            'shipping_id' => $this->shippingId->get(),
        ];
    }

    public function getChildEntities(): array
    {
        return [
            Rule::class => $this->rules,
        ];
    }

    public static function fromMappedData(array $state, array $childEntities = []): static
    {
        $shipping = new static();
        $shipping->shippingId = ShippingId::fromString($state['shipping_id']);
        $shipping->rules = $childEntities[Rule::class];

        return $shipping;
    }
}
