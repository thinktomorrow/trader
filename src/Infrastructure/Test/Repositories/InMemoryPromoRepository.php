<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Test\Repositories;

use Thinktomorrow\Trader\Domain\Model\Promo\Promo;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoId;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoState;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoRepository;
use Thinktomorrow\Trader\Domain\Model\Promo\Exceptions\CouldNotFindPromo;
use Thinktomorrow\Trader\Application\Promo\ApplicablePromo\ApplicablePromo;
use Thinktomorrow\Trader\Application\Promo\ApplicablePromo\ApplicablePromoRepository;

final class InMemoryPromoRepository implements PromoRepository, ApplicablePromoRepository
{
    /** @var Promo[] */
    public static array $promos = [];

    private string $nextReference = 'ppp-123';

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

    // For testing purposes only
    public function setNextReference(string $nextReference): void
    {
        $this->nextReference = $nextReference;
    }

    public function clear()
    {
        static::$promos = [];
    }

    public function getActivePromos(): array
    {
        $result = [];

        foreach(static::$promos as $promo) {
            if(! in_array($promo->getState(), PromoState::onlineStates())) {
                continue;
            }

            $start_at = $promo->getMappedData()['start_at'];
            $end_at = $promo->getMappedData()['end_at'];

            if($start_at && \DateTime::createFromFormat('Y-m-d H:i:s', $start_at) > now()) {
                continue;
            }

            if($end_at && \DateTime::createFromFormat('Y-m-d H:i:s', $end_at) < now()) {
                continue;
            }

            $result[] = $promo;
        }

        return $result;
    }

    public function findActivePromoByCouponCode(string $couponCode): ?ApplicablePromo
    {
        // TODO: Implement findActivePromoByCouponCode() method.
    }
}
