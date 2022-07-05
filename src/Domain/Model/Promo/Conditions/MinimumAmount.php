<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Promo\Conditions;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Model\Promo\Condition;

class MinimumAmount extends BaseCondition implements Condition
{
    private Money $amount;

    public static function getMapKey(): string
    {
        return 'minimum_amount';
    }

    public function getMappedData(): array
    {
        return array_merge(parent::getMappedData(), [
            'data' => json_encode(array_merge($this->data, ['amount' => $this->amount->getAmount()])),
        ]);
    }

    public static function fromMappedData(array $state, array $aggregateState): static
    {
        $condition = parent::fromMappedData($state, $aggregateState);

        $data = json_decode($state['data'], true);
        $condition->amount = Cash::make($data['amount']);

        return $condition;
    }
}
