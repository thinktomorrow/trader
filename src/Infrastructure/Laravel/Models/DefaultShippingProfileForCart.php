<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models;

use Thinktomorrow\Trader\Application\Cart\ShippingProfile\ShippingProfileForCart;
use Thinktomorrow\Trader\Application\Common\RendersData;

class DefaultShippingProfileForCart implements ShippingProfileForCart
{
    use RendersData;

    private string $shippingProfileId;
    private string $providerId;
    private bool $requiresAddress;
    private iterable $images;

    final private function __construct()
    {
    }

    public static function fromMappedData(array $state): static
    {
        $object = new static();
        $object->shippingProfileId = $state['shipping_profile_id'];
        $object->providerId = $state['provider_id'];
        $object->requiresAddress = $state['requires_address'];
        $object->data = json_decode($state['data'], true);
        $object->images = [];

        return $object;
    }

    public function getShippingProfileId(): string
    {
        return $this->shippingProfileId;
    }

    public function getProviderId(): string
    {
        return $this->providerId;
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

    public function setImages(iterable $images): void
    {
        $this->images = $images;
    }

    public function getImages(): iterable
    {
        return $this->images;
    }
}
