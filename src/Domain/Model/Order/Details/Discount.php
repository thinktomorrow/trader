<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Details;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Cash\Price;
use Thinktomorrow\Trader\Domain\Model\Order\Quantity;
use Thinktomorrow\Trader\Domain\Common\Taxes\TaxRate;
use Thinktomorrow\Trader\Domain\Common\Entity\ChildEntity;
use Thinktomorrow\Trader\Domain\Model\Discount\DiscountId;
use Thinktomorrow\Trader\Domain\Model\Discount\DiscountTotal;
use Thinktomorrow\Trader\Domain\Model\Product\ProductUnitPrice;
use Thinktomorrow\Trader\Domain\Model\Discount\DiscountBaseTotal;

final class Discount
{
    private DiscountId $discountId;
    private DiscountTotal $discountTotal;

    private function __construct(DiscountId $discountId, DiscountTotal $discountTotal)
    {
        $this->discountId = $discountId;
        $this->discountTotal = $discountTotal;
    }

    public function getTotalPrice(): DiscountTotal
    {
        return $this->discountTotal;
    }

    public static function fromMappedData(array $state, array $aggregateState): static
    {
        return new static(
            DiscountId::fromString($state['type']),
            DiscountTotal::fromScalars($state['total'], 'EUR', $state['tax_rate'], $state['includes_vat']),
        );
    }
}
