<?php

declare(strict_types=1);

namespace Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Model\Promo\CombinationPolicy;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\DiscountableType;
use Thinktomorrow\Trader\Domain\Model\Promo\Promo;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoId;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryPromoRepository;

final class PromoAvailabilityTest extends TestCase
{
    protected function setUp(): void
    {
        InMemoryPromoRepository::clear();
    }

    public function test_activation_method_is_independent_of_who_manages_the_promo(): void
    {
        $repository = new InMemoryPromoRepository;

        $systemAutomatic = Promo::create(PromoId::fromString('system-auto'), null, null, null, new CombinationPolicy(DiscountableType::cases()));
        $systemAutomatic->setAsSystemPromo();
        $adminAutomatic = Promo::create(PromoId::fromString('admin-auto'), null, null, null, new CombinationPolicy(DiscountableType::cases()));
        $systemCode = Promo::create(PromoId::fromString('system-code'), 'SYSTEM', null, null, new CombinationPolicy(DiscountableType::cases()));
        $systemCode->setAsSystemPromo();
        $adminCode = Promo::create(PromoId::fromString('admin-code'), 'ADMIN', null, null, new CombinationPolicy(DiscountableType::cases()));

        foreach ([$systemAutomatic, $adminAutomatic, $systemCode, $adminCode] as $promo) {
            $repository->save($promo);
        }

        $this->assertSame([$systemAutomatic, $adminAutomatic], $repository->getAvailableAutomaticPromos());
        $this->assertSame($systemCode, $repository->findAvailableByCouponCode('SYSTEM'));
        $this->assertSame($adminCode, $repository->findAvailableByCouponCode('ADMIN'));
    }
}
