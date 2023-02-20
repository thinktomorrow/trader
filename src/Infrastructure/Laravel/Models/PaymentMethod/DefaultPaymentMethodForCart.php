<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\PaymentMethod;

use Thinktomorrow\Trader\Application\Cart\PaymentMethod\PaymentMethodForCart;
use Thinktomorrow\Trader\Application\Common\RendersData;

class DefaultPaymentMethodForCart implements PaymentMethodForCart
{
    use RendersData;

    private string $paymentMethodId;
    private string $providerId;
    private iterable $images;

    final private function __construct()
    {
    }

    public static function fromMappedData(array $state): static
    {
        $object = new static();
        $object->paymentMethodId = $state['payment_method_id'];
        $object->providerId = $state['provider_id'];
        $object->data = json_decode($state['data'], true);
        $object->images = [];

        return $object;
    }

    public function getPaymentMethodId(): string
    {
        return $this->paymentMethodId;
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

    public function setImages(iterable $images): void
    {
        $this->images = $images;
    }

    public function getImages(): iterable
    {
        return $this->images;
    }
}
