<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Discount;

use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Cash\Percentage;
use Thinktomorrow\Trader\Domain\Common\Entity\ChildEntity;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultItemDiscount;
use Thinktomorrow\Trader\Domain\Common\Price\ItemDiscount;
use Thinktomorrow\Trader\Domain\Common\Price\Price;
use Thinktomorrow\Trader\Domain\Common\Price\WithPriceInputMode;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Promo\DiscountId as PromoDiscountId;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoId;

final class Discount implements ChildEntity
{
    use HasData;
    use WithPriceInputMode;

    public readonly OrderId $orderId;
    public readonly DiscountId $discountId;
    public readonly DiscountableType $discountableType;
    public readonly DiscountableId $discountableId;
    public readonly ?PromoId $promoId;
    public readonly ?PromoDiscountId $promoDiscountId;
    private readonly ItemDiscount $itemDiscount;

    private function __construct()
    {
    }

    public function getItemDiscount(): ItemDiscount
    {
        return $this->itemDiscount;
    }

    public function getPercentage(Price $price): Percentage
    {
        return Cash::from($this->itemDiscount->getIncludingVat())->asPercentage($price->getIncludingVat());
    }

    public function getMappedData(): array
    {
        $data = $this->addDataIfNotNull([
            'promo_id' => $this->promoId?->get(),
            'promo_discount_id' => $this->promoDiscountId?->get(),
        ]);

        $includesVat = $this->priceEnteredIncludesVat();

        return [
            'order_id' => $this->orderId->get(),
            'discountable_type' => $this->discountableType->value,
            'discountable_id' => $this->discountableId->get(),
            'discount_id' => $this->discountId->get(),
            'promo_id' => $this->promoId?->get(),
            'promo_discount_id' => $this->promoDiscountId?->get(),
            'total' => $includesVat ? $this->itemDiscount->getIncludingVat()->getAmount() : $this->itemDiscount->getExcludingVat()->getAmount(),
            'tax_rate' => $this->itemDiscount->getVatPercentage()->get(),
            'includes_vat' => $includesVat,
            'data' => json_encode($data),
        ];
    }

    public static function create(OrderId $orderId, DiscountId $discountId, DiscountableType $discountableType, DiscountableId $discountableId, PromoId $promoId, PromoDiscountId $promoDiscountId, ItemDiscount $itemDiscount, array $data): static
    {
        $discount = new static();

        $discount->setPriceEnteredIncludingVat($itemDiscount->hasOriginalIncludingVat());

        $discount->orderId = $orderId;
        $discount->discountId = $discountId;
        $discount->discountableType = $discountableType;
        $discount->discountableId = $discountableId;
        $discount->promoId = $promoId;
        $discount->promoDiscountId = $promoDiscountId;
        $discount->itemDiscount = $itemDiscount;
        $discount->data = $data;

        return $discount;
    }

    public static function fromMappedData(array $state, array $aggregateState): static
    {
        $discount = new static();

        $discount->setPriceEnteredIncludingVat($state['includes_vat']);

        $discount->orderId = OrderId::fromString($aggregateState['order_id']);
        $discount->discountId = DiscountId::fromString($state['discount_id']);
        $discount->discountableType = DiscountableType::from($state['discountable_type']);
        $discount->discountableId = DiscountableId::fromString($state['discountable_id']);
        $discount->promoId = $state['promo_id'] ? PromoId::fromString($state['promo_id']) : null;
        $discount->promoDiscountId = $state['promo_discount_id'] ? PromoDiscountId::fromString($state['promo_discount_id']) : null;
        $discount->itemDiscount = DefaultItemDiscount::fromMoney(Cash::make($state['total']), VatPercentage::fromString($state['tax_rate']), $state['includes_vat']);
        $discount->data = json_decode($state['data'], true);

        return $discount;
    }
}
