<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Promo;

use Thinktomorrow\Trader\Domain\Common\Entity\Aggregate;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;

class Promo implements Aggregate
{
    use HasData;

    public readonly PromoId $promoId;
    private DiscountType $discountType;

    public static function create(PromoId $promoId, DiscountType $discountType): static
    {
        $promo = new static();
        $promo->promoId = $promoId;
        $promo->discountType = $discountType;

        return $promo;
    }

    public function getMappedData(): array
    {
        return [
            'promo_id' => $this->promoId->get(),
            'discount_type' => $this->discountType->value,
            'data' => json_encode($this->data),
        ];
    }

    public function getChildEntities(): array
    {
        return [];
    }

    public static function fromMappedData(array $state, array $childEntities = []): static
    {
        $promo = new static();
        $promo->promoId = PromoId::fromString($state['promo_id']);
        $promo->discountType = DiscountType::from($state['discount_type']);
        $promo->data = json_decode($state['promo_id'], true);

        return $promo;
    }
}
