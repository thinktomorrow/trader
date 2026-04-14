<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\OrderRead;

use Thinktomorrow\Trader\Application\Common\RendersData;
use Thinktomorrow\Trader\Application\Common\RendersMoney;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingState;

abstract class OrderReadShipping
{
    use RendersData;
    use RendersMoney;
    use WithFormattedServiceTotals;
    use WithServiceTotals;

    protected string $shipping_id;

    protected ?string $shipping_profile_id;

    protected ShippingState $state;

    protected iterable $discounts;

    protected array $data;

    final public function __construct() {}

    public static function fromMappedData(array $state, array $orderState, iterable $discounts): static
    {
        $shipping = new static;

        if (! $state['shipping_state'] instanceof ShippingState) {
            throw new \InvalidArgumentException('Shipping state is expected to be instance of ShippingState. Instead '.gettype($state['shipping_state']).' is passed.');
        }

        $shipping->shipping_id = $state['shipping_id'];
        $shipping->shipping_profile_id = $state['shipping_profile_id'] ?: null;
        $shipping->state = $state['shipping_state'];
        $shipping->data = json_decode($state['data'], true);
        $shipping->discounts = $discounts;

        $shipping->initializeServiceTotalsFromState($state);

        return $shipping;
    }

    public function getShippingId(): string
    {
        return $this->shipping_id;
    }

    public function getShippingProfileId(): ?string
    {
        return $this->shipping_profile_id;
    }

    public function getDiscounts(): iterable
    {
        return $this->discounts;
    }

    public function requiresAddress(): bool
    {
        return $this->data('requires_address');
    }

    public function getTitle(): ?string
    {
        return $this->data('title');
    }

    public function getDescription(): ?string
    {
        return $this->data('description');
    }

    public function getData(string $key, $default = null): mixed
    {
        return $this->data($key, null, $default);
    }
}
