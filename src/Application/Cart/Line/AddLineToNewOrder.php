<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart\Line;

use Thinktomorrow\Trader\Domain\Model\Order\Line\Quantity;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;

class AddLineToNewOrder
{
    private string $variantId;
    private int $quantity;
    private array $data;

    public function __construct(string $variantId, int $quantity, array $data)
    {
        $this->variantId = $variantId;
        $this->quantity = $quantity;
        $this->data = $data;
    }

    public function getVariantId(): VariantId
    {
        return VariantId::fromString($this->variantId);
    }

    public function getQuantity(): Quantity
    {
        return Quantity::fromInt($this->quantity);
    }

    public function getData(): array
    {
        return $this->data;
    }
}