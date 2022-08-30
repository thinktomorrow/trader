<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart\Line;

use Thinktomorrow\Trader\Domain\Model\Order\Line\Quantity;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;

final class AddLine
{
    private string $orderId;
    private string $variantId;
    private int $quantity;
    private array $personalisations;
    private array $data;

    public function __construct(string $orderId, string $variantId, int $quantity, array $personalisations, array $data)
    {
        $this->orderId = $orderId;
        $this->variantId = $variantId;
        $this->quantity = $quantity;
        $this->personalisations = $personalisations;
        $this->data = $data;
    }

    public function getOrderId(): OrderId
    {
        return OrderId::fromString($this->orderId);
    }

    public function getVariantId(): VariantId
    {
        return VariantId::fromString($this->variantId);
    }

    public function getQuantity(): Quantity
    {
        return Quantity::fromInt($this->quantity);
    }

    public function getPersonalisations(): array
    {
        return $this->personalisations;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
