<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Test\Repositories;

use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\PromoDiscountId;
use Thinktomorrow\Trader\Domain\Model\Promo\Exceptions\CouldNotFindPromo;
use Thinktomorrow\Trader\Domain\Model\Promo\Promo;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoId;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoRepository;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoState;

final class InMemoryPromoRepository implements InMemoryRepository, PromoRepository
{
    /** @var Promo[] */
    public static array $promos = [];

    private string $nextReference = 'ppp-123';

    private int $nextDiscountReference = 123;

    public function save(Promo $promo): void
    {
        self::$promos[$promo->promoId->get()] = $promo;
    }

    public function find(PromoId $promoId): Promo
    {
        if (! isset(self::$promos[$promoId->get()])) {
            throw new CouldNotFindPromo('No promo found by id '.$promoId);
        }

        return self::$promos[$promoId->get()];
    }

    public function delete(PromoId $promoId): void
    {
        if (! isset(self::$promos[$promoId->get()])) {
            throw new CouldNotFindPromo('No promo found by id '.$promoId);
        }

        unset(self::$promos[$promoId->get()]);
    }

    public function nextReference(): PromoId
    {
        return PromoId::fromString($this->nextReference);
    }

    public function nextPromoDiscountReference(): PromoDiscountId
    {
        return PromoDiscountId::fromString('ddd-'.$this->nextDiscountReference++);
    }

    // For testing purposes only
    public function setNextReference(string $nextReference): void
    {
        $this->nextReference = $nextReference;
    }

    public static function clear()
    {
        self::$promos = [];
    }

    public function getAvailableAutomaticPromos(): array
    {
        $result = [];

        foreach ($this->filterActivePromos() as $promo) {
            if ($promo->getCouponCode() !== null) {
                continue;
            }
            $result[] = $promo;
        }

        return $result;
    }

    public function findAvailableByCouponCode(string $couponCode): ?Promo
    {
        foreach ($this->filterActivePromos() as $promo) {
            if ($promo->getCouponCode() !== null && $promo->getCouponCode() === $couponCode) {
                return $promo;
            }
        }

        return null;
    }

    private function filterActivePromos(): array
    {
        $result = [];

        foreach (self::$promos as $promo) {
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
}
