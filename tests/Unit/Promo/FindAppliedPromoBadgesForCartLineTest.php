<?php

declare(strict_types=1);

namespace Tests\Unit\Promo;

use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Application\Cart\Read\CartDiscount;
use Thinktomorrow\Trader\Application\Cart\Read\CartLine;
use Thinktomorrow\Trader\Application\Promo\Preview\FindAppliedPromoBadgesForCartLine;
use Thinktomorrow\Trader\Domain\Model\Promo\CombinationPolicy;
use Thinktomorrow\Trader\Domain\Model\Promo\Conditions\Condition;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\SalePriceSystemDiscount;
use Thinktomorrow\Trader\Domain\Model\Promo\Promo;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoId;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoRepository;

final class FindAppliedPromoBadgesForCartLineTest extends TestCase
{
    public function test_it_only_returns_badges_for_applied_discounts_with_the_cart_line_flag(): void
    {
        $promo = Promo::create(PromoId::fromString('visible'), null, null, null, new CombinationPolicy([]));
        $promo->addData(['show_on_cart_line' => true, 'product_badge_text' => ['nl' => 'Korting toegepast']]);
        $promo->addPromoDiscount(SalePriceSystemDiscount::fromMappedData(
            ['discount_id' => 'visible-discount', 'data' => '{}'],
            ['promo_id' => 'visible'],
            [Condition::class => []],
        ));

        $repository = $this->createMock(PromoRepository::class);
        $repository->method('getAvailablePromos')->willReturn([$promo]);
        $applied = $this->createMock(CartDiscount::class);
        $applied->method('getData')->with('promo_discount_id')->willReturn('visible-discount');
        $line = $this->createMock(CartLine::class);
        $line->method('getDiscounts')->willReturn([$applied]);

        $query = new FindAppliedPromoBadgesForCartLine($repository);

        $this->assertSame('Korting toegepast', $query->get($line, 'nl')->first()->productBadgeText);
        $this->assertSame('visible', $query->get($line, 'nl')->first()->promoId);
    }
}
