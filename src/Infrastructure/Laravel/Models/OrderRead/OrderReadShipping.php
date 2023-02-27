<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\OrderRead;

use Thinktomorrow\Trader\Application\Common\RendersData;
use Thinktomorrow\Trader\Application\Common\RendersMoney;
use Thinktomorrow\Trader\Domain\Common\Price\Price;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingState;

abstract class OrderReadShipping
{
    use RendersData;
    use RendersMoney;

    protected Price $cost;
    protected string $shipping_id;
    protected ?string $shipping_profile_id;
    protected ShippingState $state;
    protected iterable $discounts;
    protected array $data;

    // General flag for all line prices to render with or without tax.
    protected bool $include_tax = true;

    final public function __construct()
    {
    }

    public static function fromMappedData(array $state, array $orderState, iterable $discounts): static
    {
        $shipping = new static();

        if (! $state['shipping_state'] instanceof  ShippingState) {
            throw new \InvalidArgumentException('Shipping state is expected to be instance of ShippingState. Instead ' . gettype($state['shipping_state']) . ' is passed.');
        }

        $shipping->shipping_id = $state['shipping_id'];
        $shipping->shipping_profile_id = $state['shipping_profile_id'] ?: null;
        $shipping->state = $state['shipping_state'];
        $shipping->cost = $state['cost'];
        $shipping->data = json_decode($state['data'], true);
        $shipping->discounts = $discounts;

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

    public function getCostPrice(): string
    {
        return $this->renderMoney(
            $this->include_tax ? $this->cost->getIncludingVat() : $this->cost->getExcludingVat(),
            $this->getLocale()
        );
    }

    public function includeTax(bool $includeTax = true): void
    {
        $this->include_tax = $includeTax;
    }

    public function getDiscounts(): iterable
    {
        // TODO: Implement getDiscounts() method.
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
}
