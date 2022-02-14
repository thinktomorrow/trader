<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Details;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Cash\Price;
use Thinktomorrow\Trader\Domain\Model\Order\Quantity;
use Thinktomorrow\Trader\Domain\Common\Taxes\TaxRate;
use Thinktomorrow\Trader\Domain\Common\Entity\ChildEntity;
use Thinktomorrow\Trader\Domain\Model\Product\ProductUnitPrice;

final class Line
{
    private ProductUnitPrice $productUnitPrice;
    private Quantity $quantity;

    private function __construct(ProductUnitPrice $productUnitPrice, Quantity $quantity)
    {
        $this->productUnitPrice = $productUnitPrice;
        $this->quantity = $quantity;
    }

    public function getTotal(): Price
    {
        return $this->productUnitPrice
            ->multiply($this->quantity->asInt());
    }

    public static function fromMappedData(array $state, array $aggregateState): static
    {
        return new static(
            ProductUnitPrice::fromScalars(
                $state['product_unit_price'],
                'EUR',
                $state['tax_rate'],
                $state['includes_vat']
            ),
            Quantity::fromInt($state['quantity'])
        );
    }
}
