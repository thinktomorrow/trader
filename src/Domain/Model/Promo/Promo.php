<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Promo;

use Assert\Assertion;
use Thinktomorrow\Trader\Domain\Common\Entity\Aggregate;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;
use Thinktomorrow\Trader\Domain\Common\Event\RecordsEvents;
use Thinktomorrow\Trader\Domain\Model\Promo\Events\PromoCreated;

class Promo implements Aggregate
{
    use HasData;
    use RecordsEvents;

    public readonly PromoId $promoId;
    private PromoState $state;
    private ?string $coupon_code;
    private ?\DateTime $startAt;
    private ?\DateTime $endAt;

    /** @var Discount[] */
    private array $discounts;

    public static function create(PromoId $promoId): static
    {
        $promo = new static();
        $promo->promoId = $promoId;
        $promo->state = PromoState::online;
        $promo->coupon_code = null;
        $promo->startAt = null;
        $promo->endAt = null;

        $promo->recordEvent(new PromoCreated($promoId));

        return $promo;
    }

    public function updateState(PromoState $state): void
    {
        $this->state = $state;
    }

    public function getState(): PromoState
    {
        return $this->state;
    }

    public function updateDiscounts(array $discounts): void
    {
        Assertion::allIsInstanceOf($discounts, Discount::class);

        $this->discounts = $discounts;
    }

    public function getDiscounts(): array
    {
        return $this->discounts;
    }

    public function getMappedData(): array
    {
        return [
            'promo_id' => $this->promoId->get(),
            'state' => $this->state->value,
            'coupon_code' => $this->coupon_code,
            'start_at' => $this->startAt?->format('Y-m-d H:i:s'),
            'end_at' => $this->endAt?->format('Y-m-d H:i:s'),
            'data' => json_encode($this->data),
        ];
    }

    public function getChildEntities(): array
    {
        return [
            Discount::class => array_map(fn (Discount $discount) => $discount->getMappedData(), $this->discounts),
        ];
    }

    public static function fromMappedData(array $state, array $childEntities = []): static
    {
        Assertion::allIsInstanceOf($childEntities[Discount::class], Discount::class);

        $promo = new static();

        $promo->promoId = PromoId::fromString($state['promo_id']);
        $promo->state = PromoState::from($state['state']);
        $promo->coupon_code = $state['coupon_code'];
        $promo->startAt = $state['start_at'] ? \DateTime::createFromFormat('Y-m-d H:i:s', $state['start_at']) : null;
        $promo->endAt = $state['end_at'] ? \DateTime::createFromFormat('Y-m-d H:i:s', $state['end_at']) : null;
        $promo->data = json_decode($state['data'], true);
        $promo->discounts = $childEntities[Discount::class];

//        $promo->discounts = array_key_exists(Discount::class, $childEntities)
//            ? array_map(fn ($discountState) => Discount::fromMappedData($discountState, $state, [Condition::class => $discountState[Condition::class]]), $childEntities[Discount::class])
//            : [];


        return $promo;
    }
}
