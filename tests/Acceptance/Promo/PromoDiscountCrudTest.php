<?php

declare(strict_types=1);

namespace Tests\Acceptance\Promo;

use Tests\Acceptance\TestCase;
use Thinktomorrow\Trader\Application\Promo\CUD\CreatePromo;
use Thinktomorrow\Trader\Application\Promo\CUD\CreatePromoDiscount;
use Thinktomorrow\Trader\Application\Promo\CUD\DeletePromoDiscount;
use Thinktomorrow\Trader\Application\Promo\CUD\UpdatePromoDiscount;
use Thinktomorrow\Trader\Domain\Model\Promo\Conditions\MinimumLinesQuantity;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\DiscountableType;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\FixedAmountDiscount;
use Thinktomorrow\Trader\Domain\Model\Promo\Exceptions\CouldNotFindPromoDiscount;

class PromoDiscountCrudTest extends TestCase
{
    public function test_it_can_create_a_discount_with_conditions(): void
    {
        $application = $this->orderContext->apps()->promoApplication();
        $promoId = $application->createPromo(new CreatePromo(null, null, null, [], false, []));

        $discountId = $application->createPromoDiscount(new CreatePromoDiscount(
            $promoId->get(),
            'fixed_amount',
            [['key' => 'minimum_lines_quantity', 'data' => ['minimum_quantity' => '2']]],
            ['amount' => '50'],
        ));

        $discount = $this->orderContext->repos()->promoRepository()->find($promoId)->findPromoDiscount($discountId);
        $this->assertInstanceOf(FixedAmountDiscount::class, $discount);
        $this->assertSame(DiscountableType::order, $discount->getDiscountableType());
        $this->assertSame('50', $discount->getData('amount'));
        $this->assertInstanceOf(MinimumLinesQuantity::class, $discount->getConditions()[0]);
    }

    public function test_it_can_update_one_discount_and_replace_its_conditions(): void
    {
        $application = $this->orderContext->apps()->promoApplication();
        $promoId = $application->createPromo(new CreatePromo(null, null, null, [], false, []));
        $first = $application->createPromoDiscount(new CreatePromoDiscount($promoId->get(), 'fixed_amount', [], ['amount' => '50']));
        $second = $application->createPromoDiscount(new CreatePromoDiscount($promoId->get(), 'percentage_off', [], ['percentage' => '10']));

        $application->updatePromoDiscount(new UpdatePromoDiscount(
            $promoId->get(),
            $first->get(),
            'fixed_amount',
            [['key' => 'minimum_lines_quantity', 'data' => ['minimum_quantity' => '3']]],
            ['amount' => '75'],
        ));

        $promo = $this->orderContext->repos()->promoRepository()->find($promoId);
        $this->assertCount(2, $promo->getDiscounts());
        $this->assertSame('75', $promo->findPromoDiscount($first)->getData('amount'));
        $this->assertInstanceOf(MinimumLinesQuantity::class, $promo->findPromoDiscount($first)->getConditions()[0]);
        $this->assertSame('10', $promo->findPromoDiscount($second)->getData('percentage'));

        $application->updatePromoDiscount(new UpdatePromoDiscount($promoId->get(), $first->get(), 'fixed_amount', [], ['amount' => '80']));
        $this->assertSame([], $this->orderContext->repos()->promoRepository()->find($promoId)->findPromoDiscount($first)->getConditions());
    }

    public function test_it_can_delete_one_discount_without_removing_the_other(): void
    {
        $application = $this->orderContext->apps()->promoApplication();
        $promoId = $application->createPromo(new CreatePromo(null, null, null, [], false, []));
        $first = $application->createPromoDiscount(new CreatePromoDiscount($promoId->get(), 'fixed_amount', [], ['amount' => '50']));
        $second = $application->createPromoDiscount(new CreatePromoDiscount($promoId->get(), 'percentage_off', [], ['percentage' => '10']));

        $application->deletePromoDiscount(new DeletePromoDiscount($promoId->get(), $first->get()));

        $promo = $this->orderContext->repos()->promoRepository()->find($promoId);
        $this->assertCount(1, $promo->getDiscounts());
        $this->assertEquals($second, $promo->getDiscounts()[0]->getDiscountId());
    }

    public function test_it_cannot_update_a_missing_discount(): void
    {
        $application = $this->orderContext->apps()->promoApplication();
        $promoId = $application->createPromo(new CreatePromo(null, null, null, [], false, []));

        $this->expectException(CouldNotFindPromoDiscount::class);
        $application->updatePromoDiscount(new UpdatePromoDiscount($promoId->get(), 'missing', 'fixed_amount', [], ['amount' => '50']));
    }
}
