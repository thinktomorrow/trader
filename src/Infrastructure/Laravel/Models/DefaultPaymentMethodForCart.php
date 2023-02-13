<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models;

use Thinktomorrow\Trader\Application\Cart\PaymentMethod\PaymentMethodForCart;
use Thinktomorrow\Trader\Application\Common\RendersData;

class DefaultPaymentMethodForCart implements PaymentMethodForCart
{
    use RendersData;

    private string $paymentMethodId;
    private iterable $images;

    final private function __construct()
    {
    }

    public static function fromMappedData(array $state): static
    {
        $object = new static();
        $object->paymentMethodId = $state['payment_method_id'];
        $object->data = json_decode($state['data'], true);
        $object->images = [];

        return $object;
    }

    public function getPaymentMethodId(): string
    {
        return $this->paymentMethodId;
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
