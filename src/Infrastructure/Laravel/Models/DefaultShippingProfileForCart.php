<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models;

use Thinktomorrow\Trader\Application\Cart\ShippingProfile\ShippingProfileForCart;
use Thinktomorrow\Trader\Application\Common\RendersData;

class DefaultShippingProfileForCart implements ShippingProfileForCart
{
    use RendersData;

    private string $shippingProfileId;
    private bool $requiresAddress;

    final private function __construct(){}

    public static function fromMappedData(array $state): static
    {
        $object = new static();
        $object->shippingProfileId = $state['shipping_profile_id'];
        $object->requiresAddress = $state['requires_address'];
        $object->data = json_decode($state['data'], true);

        return $object;
    }

    public function getShippingProfileId(): string
    {
        return $this->shippingProfileId;
    }

    public function getTitle(): string
    {
        return $this->data('label', null, '');
    }

    public function getDescription(): ?string
    {
        return $this->data('description', null, $this->getTitle());
    }

    public function requiresAddress(): bool
    {
        return $this->requiresAddress;
    }
}
