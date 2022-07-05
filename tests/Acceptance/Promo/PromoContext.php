<?php
declare(strict_types=1);

namespace Tests\Acceptance\Promo;

use Tests\Acceptance\TestCase;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;
use Thinktomorrow\Trader\Domain\Model\Promo\DiscountFactory;
use Thinktomorrow\Trader\Domain\Model\Promo\ConditionFactory;
use Thinktomorrow\Trader\Infrastructure\Test\TestTraderConfig;
use Thinktomorrow\Trader\Infrastructure\Test\EventDispatcherSpy;
use Thinktomorrow\Trader\Application\Promo\CUD\PromoApplication;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\FixedAmountDiscount;
use Thinktomorrow\Trader\Application\Promo\OrderPromo\OrderDiscountFactory;
use Thinktomorrow\Trader\Domain\Model\Promo\Conditions\MinimumLinesQuantity;
use Thinktomorrow\Trader\Application\Promo\OrderPromo\OrderConditionFactory;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryPromoRepository;
use Thinktomorrow\Trader\Application\Promo\OrderPromo\Discounts\FixedAmountOrderDiscount;
use Thinktomorrow\Trader\Application\Promo\OrderPromo\Discounts\PercentageOffOrderDiscount;


class PromoContext extends TestCase
{
    protected PromoApplication $promoApplication;
    protected InMemoryPromoRepository $promoRepository;

    protected function setUp(): void
    {
        parent::setUp();

        (new TestContainer())->add(DiscountFactory::class, new DiscountFactory([
            FixedAmountDiscount::class,
            PercentageOffOrderDiscount::class,
        ], new ConditionFactory([
            MinimumLinesQuantity::class,
        ])));

        (new TestContainer())->add(OrderDiscountFactory::class, new OrderDiscountFactory([
            FixedAmountOrderDiscount::class,
            PercentageOffOrderDiscount::class,
        ], new OrderConditionFactory([
            \Thinktomorrow\Trader\Application\Promo\OrderPromo\Conditions\MinimumLinesQuantityOrderCondition::class,
        ])));

        $this->promoApplication = new PromoApplication(
            new TestTraderConfig(),
            $this->eventDispatcher = new EventDispatcherSpy(),
            $this->promoRepository = new InMemoryPromoRepository(
                (new TestContainer())->get(DiscountFactory::class),
                (new TestContainer())->get(OrderDiscountFactory::class),
            ),
            (new TestContainer())->get(DiscountFactory::class),
        );
    }

    public function tearDown(): void
    {
        $this->promoRepository->clear();
    }
}
