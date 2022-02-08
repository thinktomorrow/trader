<?php

namespace Thinktomorrow\Trader\Tests\Discounts;

use Optiphar\Discounts\Application\ApplicableDiscounts;
use Optiphar\Promos\Common\Domain\Discount\ExtraProductDiscountPercentage;
use Optiphar\Promos\Common\Domain\Rules\IsCombinable;
use Optiphar\Promos\Common\Domain\Rules\MinimumAmount;
use Optiphar\Promos\Common\Domain\Rules\PromoGroup;
use Tests\Factories\CartFactory;
use Tests\Factories\ProductFactory;
use Tests\Factories\PromoFactory;
use Tests\OptipharDatabaseTransactions;
use Tests\TestCase;
use Thinktomorrow\Trader\Tests\Cart\CartHelpers;

class ApplicableDiscountsTest extends TestCase
{
    use OptipharDatabaseTransactions,
        CartHelpers;

    private $cart;

    public function setUp()
    {
        parent::setUp();

        $this->setUpDatabase();

        $product = ProductFactory::create(['slug:nl' => 'hijkmk', 'gross_amount' => 100])->toProductRead();
        $this->cart = CartFactory::createEmpty()->addProduct($product)->get();
    }

    /** @test */
    public function it_can_collect_the_applicable_discounts_for_a_given_cart()
    {
        $promo = PromoFactory::createOrderPromo(['discount' => new ExtraProductDiscountPercentage(60)], [])->get();

        $discountsCollection = app(ApplicableDiscounts::class)->get($this->cart);
        $appliedDiscount = $discountsCollection->first();

        $this->assertCount(1, $discountsCollection);
        $this->assertEquals($promo->getId()->get(), $appliedDiscount->id()->get());
    }

    /** @test */
    public function it_collects_only_the_applicable_discount_with_the_highest_impact()
    {
        $promo = PromoFactory::createOrderPromo(['discount' => new ExtraProductDiscountPercentage(60)], [])->get();
        PromoFactory::createOrderPromo(['discount' => new ExtraProductDiscountPercentage(50)], []);

        $discountsCollection = app(ApplicableDiscounts::class)->get($this->cart);
        $appliedDiscount = $discountsCollection->first();

        $this->assertCount(1, $discountsCollection);
        $this->assertEquals($promo->getId()->get(), $appliedDiscount->id()->get());
    }

    /** @test */
    public function it_should_not_collect_a_discount_that_is_not_applicable()
    {
        PromoFactory::createOrderPromo([
            'discount' => new ExtraProductDiscountPercentage(50),
            'minimumAmount' => MinimumAmount::fromEUR(1000),
        ], []);

        $discountsCollection = app(ApplicableDiscounts::class)->get($this->cart);

        $this->assertCount(0, $discountsCollection);
    }

    /** @test */
    public function a_combinable_discount_that_is_applicable_is_added_as_well()
    {
        $promo = PromoFactory::createOrderPromo(['discount' => new ExtraProductDiscountPercentage(60)], [])->get();
        PromoFactory::createOrderPromo(['discount' => new ExtraProductDiscountPercentage(20)], [
            'IsCombinable' => new IsCombinable(),
        ]);

        $discountsCollection = app(ApplicableDiscounts::class)->get($this->cart);

        $this->assertCount(2, $discountsCollection);
        $this->assertEquals($promo->getId()->get(), $discountsCollection->first()->id()->get());
    }

    /** @test */
    public function a_discount_is_not_combined_with_a_discount_from_same_group()
    {
        $promo = PromoFactory::createOrderPromo(['discount' => new ExtraProductDiscountPercentage(60)], [
            'PromoGroup' => new PromoGroup('new-promogroup'),
        ])->get();
        PromoFactory::createOrderPromo(['discount' => new ExtraProductDiscountPercentage(20)], [
            'IsCombinable' => new IsCombinable(),
            'PromoGroup' => new PromoGroup('new-promogroup'),
        ]);

        $discountsCollection = app(ApplicableDiscounts::class)->get($this->cart);

        $this->assertCount(1, $discountsCollection);
        $this->assertEquals($promo->getId()->get(), $discountsCollection->first()->id()->get());
    }
}
