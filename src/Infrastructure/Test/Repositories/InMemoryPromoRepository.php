<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Test\Repositories;

use Thinktomorrow\Trader\Application\Promo\OrderPromo\OrderDiscount;
use Thinktomorrow\Trader\Application\Promo\OrderPromo\OrderDiscountFactory;
use Thinktomorrow\Trader\Application\Promo\OrderPromo\OrderPromo;
use Thinktomorrow\Trader\Application\Promo\OrderPromo\OrderPromoRepository;
use Thinktomorrow\Trader\Domain\Model\Promo\Condition;
use Thinktomorrow\Trader\Domain\Model\Promo\Discount;
use Thinktomorrow\Trader\Domain\Model\Promo\DiscountFactory;
use Thinktomorrow\Trader\Domain\Model\Promo\DiscountId;
use Thinktomorrow\Trader\Domain\Model\Promo\Exceptions\CouldNotFindPromo;
use Thinktomorrow\Trader\Domain\Model\Promo\Promo;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoId;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoRepository;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoState;

final class InMemoryPromoRepository implements PromoRepository, OrderPromoRepository, InMemoryRepository
{
    /** @var Promo[] */
    public static array $promos = [];

    private string $nextReference = 'ppp-123';
    private string $nextDiscountReference = 'ddd-123';

    private DiscountFactory $discountFactory;
    private OrderDiscountFactory $orderDiscountFactory;

    public function __construct(DiscountFactory $discountFactory, OrderDiscountFactory $orderDiscountFactory)
    {
        $this->discountFactory = $discountFactory;
        $this->orderDiscountFactory = $orderDiscountFactory;
    }

    public function save(Promo $promo): void
    {
        static::$promos[$promo->promoId->get()] = $promo;
    }

    public function find(PromoId $promoId): Promo
    {
        if (! isset(static::$promos[$promoId->get()])) {
            throw new CouldNotFindPromo('No promo found by id ' . $promoId);
        }

        return static::$promos[$promoId->get()];
    }

    public function delete(PromoId $promoId): void
    {
        if (! isset(static::$promos[$promoId->get()])) {
            throw new CouldNotFindPromo('No promo found by id ' . $promoId);
        }

        unset(static::$promos[$promoId->get()]);
    }

    public function nextReference(): PromoId
    {
        return PromoId::fromString($this->nextReference);
    }

    public function nextDiscountReference(): DiscountId
    {
        return DiscountId::fromString($this->nextDiscountReference);
    }

    // For testing purposes only
    public function setNextReference(string $nextReference): void
    {
        $this->nextReference = $nextReference;
    }

    public static function clear()
    {
        static::$promos = [];
    }

    public function getAvailableOrderPromos(): array
    {
        $result = [];

        foreach ($this->filterActivePromos() as $promo) {
            if ($promo->hasCouponCode()) {
                continue;
            }
            $result[] = $this->createApplicablePromoFromPromo($promo);
        }

        return $result;
    }

    public function findOrderPromoByCouponCode(string $couponCode): ?OrderPromo
    {
        foreach ($this->filterActivePromos() as $promo) {
            if ($promo->hasCouponCode() && $promo->getCouponCode() == $couponCode) {
                return $this->createApplicablePromoFromPromo($promo);
            };
        }

        return null;
    }

    private function filterActivePromos(): array
    {
        $result = [];

        foreach (static::$promos as $promo) {
            if (! in_array($promo->getState(), PromoState::onlineStates())) {
                continue;
            }

            $start_at = $promo->getMappedData()['start_at'];
            $end_at = $promo->getMappedData()['end_at'];

            if ($start_at && \DateTime::createFromFormat('Y-m-d H:i:s', $start_at) > now()) {
                continue;
            }

            if ($end_at && \DateTime::createFromFormat('Y-m-d H:i:s', $end_at) < now()) {
                continue;
            }

            $result[] = $promo;
        }

        return $result;
    }

    private function createApplicablePromoFromPromo(Promo $promo): OrderPromo
    {
        return OrderPromo::fromMappedData(
            $promo->getMappedData(),
            [
                OrderDiscount::class => array_map(fn (Discount $discount) => $this->orderDiscountFactory->make(
                    $discount::getMapKey(),
                    $discount->getMappedData(),
                    $promo->getMappedData(),
                    array_map(fn (Condition $condition) => $condition->getMappedData(), $discount->getConditions())
                ), $promo->getDiscounts()),
            ]
        );
    }
}
