<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Promo\Discounts;

use Thinktomorrow\Trader\Domain\Common\Cash\Percentage;
use Thinktomorrow\Trader\Domain\Model\Promo\Discount;

class PercentageOffDiscount extends BaseDiscount implements Discount
{
    private Percentage $percentage;

    public static function getMapKey(): string
    {
        return 'percentage_off';
    }

    public function getMappedData(): array
    {
        return array_merge(parent::getMappedData(), [
            'data' => json_encode(array_merge($this->data, ['percentage' => $this->percentage->get()])),
        ]);
    }

    public static function fromMappedData(array $state, array $aggregateState, array $childEntities = []): static
    {
        $discount = parent::fromMappedData($state, $aggregateState, $childEntities);

        $data = json_decode($state['data'], true);
        $discount->percentage = Percentage::fromString($data['percentage']);

        return $discount;
    }
}
