<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Promo\Discounts;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Price\TaxMode;
use Thinktomorrow\Trader\Domain\Model\Promo\Discount;

final class FixedAmountDiscount extends BaseDiscount implements Discount
{
    private Money $amount;

    private TaxMode $taxMode;

    public static function getMapKey(): string
    {
        return 'fixed_amount';
    }

    public function getMappedData(): array
    {
        return array_merge(parent::getMappedData(), [
            'data' => json_encode(array_merge($this->data, [
                'amount' => $this->amount->getAmount(),
                'tax_mode' => $this->taxMode->value,
            ])),
        ]);
    }

    public static function fromMappedData(array $state, array $aggregateState, array $childEntities = []): static
    {
        $discount = parent::fromMappedData($state, $aggregateState, $childEntities);

        $data = json_decode($state['data'], true);
        $discount->amount = Cash::make($data['amount']);
        $discount->taxMode = isset($data['tax_mode']) ? TaxMode::from($data['tax_mode']) : TaxMode::Exclusive;

        return $discount;
    }

    public function getTaxMode(): TaxMode
    {
        return $this->taxMode;
    }
}
