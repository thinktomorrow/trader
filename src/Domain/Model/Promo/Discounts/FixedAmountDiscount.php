<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Promo\Discounts;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Entity\ChildAggregate;

final class FixedAmountDiscount implements ChildAggregate
{
    private Money $amount;

    public static function getMapKey(): string
    {
        return 'fixed_amount';
    }

    public function getMappedData(): array
    {
        // TODO: Implement getMappedData() method.
    }

    public function getChildEntities(): array
    {
        // TODO: Implement getChildEntities() method.
    }

    public static function fromMappedData(array $state, array $aggregateState, array $conditions): static
    {
        $values = json_decode($state['values'], true);

        $discount = new static();
        $discount->amount = Cash::make($values['amount']);
        $discount->conditions = $conditions;

        return $discount;
    }
}
