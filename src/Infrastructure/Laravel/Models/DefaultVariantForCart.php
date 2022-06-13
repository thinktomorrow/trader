<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models;

use Thinktomorrow\Trader\Application\Common\RendersData;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantSalePrice;
use Thinktomorrow\Trader\Application\Cart\VariantForCart\VariantForCart;

class DefaultVariantForCart implements VariantForCart
{
    use RendersData;

    private VariantId $variantId;
    private VariantSalePrice $variantSalePrice;

    private function __construct() {

    }

    public static function fromMappedData(array $state): static
    {
        $object = new static();

        $object->variantId = VariantId::fromString($state['variant_id']);
        $object->variantSalePrice = VariantSalePrice::fromScalars($state['sale_price'], 'EUR', $state['tax_rate'], $state['includes_vat']);
        $object->data = json_decode($state['data'], true);

        return $object;
    }

    public function getVariantId(): VariantId
    {
        return $this->variantId;
    }

    public function getSalePrice(): VariantSalePrice
    {
        return $this->variantSalePrice;
    }

    public function getTitle(): string
    {
        return $this->data('title', null, '');
    }
}
