# Upgrade Guide: 0.10.x -> 0.11.0 (discount refactor)

This guide covers the discount refactor in Trader `0.11.0`, upgrading from `0.10.x` (including `0.10.3`). Complete any earlier upgrade first: from
`0.8.x`, follow [0.8 to 0.9](upgrade-0.8-to-0.9.md) and then [0.9 to 0.10](upgrade-0.9-to-0.10.md). Do not treat this
guide as a substitute for those migrations. Test the entire sequence on a copy of production data before deployment.

## 1. Inventory application code and data

Back up the database and record the current promo, promo-discount and applied order-discount IDs and values. Search
application code (including jobs, migrations, setup commands and tests) for these old APIs and direct schema writes:

```bash
rg --glob '*.php' --glob '!vendor/**' 'CreateCouponPromo|CreateSystemPromo|CreatePromo\(|UpdatePromo\(|UpdateDiscount|UpdateCondition|ApplyPromoToOrder|PromoApplicationScope|OrderPromoRepository|OrderPromo\\|LinePromo\\|Order\\Discount\\|Model\\Promo\\(Discount|Condition|DiscountFactory|ConditionFactory)|is_combinable|isCombinable|discountable_type|trader_promo_discounts|trader_promos|AdjustOrderVatSnapshot'
```

Also inspect bindings for `OrderDiscountFactory`, `OrderConditionFactory`, `DiscountFactory` and `ConditionFactory`,
and inventory every distinct `trader_promo_discounts.key`. The built-in data backfill does **not** know about custom
discount keys. Record which promotions should combine and whether two product discounts may affect the same line.
Do not assume that the old boolean `is_combinable` captures the intended rules.

## 2. Plan the schema and data migration

With checkout and promo-writing jobs paused, run `php artisan migrate` on a copy of production data first. Trader
loads these migrations in order:

1. `2026_11_10_000000_add_promo_combination_policy`: adds nullable `trader_promos.combines_with`, a boolean
   `multiple_product_discounts_per_line` (default `false`), and nullable
   `trader_promo_discounts.discountable_type`.
2. `2026_11_10_000001_backfill_promo_combination_policy`: converts `is_combinable = false` to `[]` and `true` to
   `["line","order","shipping","payment"]`; fills `discountable_type` only for `sale_price` (`line`),
   `fixed_amount` and `percentage_off` (`order`).
3. `2026_11_10_000002_remove_legacy_promo_combinability`: fills any remaining null `combines_with`, makes it
   non-nullable and drops `is_combinable`.

There is **no automatic mapping for custom discount keys**. Before allowing the upgraded application to load promos,
backfill each custom key's target type (`line`, `order`, `shipping` or `payment`) in a project migration or a controlled
data migration. For example, Hanolux's `odoo_pricelist_price` and `pickup_discount` target `line`, while
`aqualux_free_shipping` targets `shipping`. Review actual persisted keys before applying those mappings; a custom
discount with a null type throws when the promo repository hydrates it. Check the result, including unknown keys:

```sql
-- Example only: verify these keys and targets against the project's own discount classes first.
UPDATE trader_promo_discounts SET discountable_type = 'line'
WHERE `key` IN ('odoo_pricelist_price', 'pickup_discount') AND discountable_type IS NULL;
UPDATE trader_promo_discounts SET discountable_type = 'shipping'
WHERE `key` = 'aqualux_free_shipping' AND discountable_type IS NULL;
```

Run the project backfill before restarting checkout and promo-reading jobs. Then inspect:

```sql
SELECT `key`, discountable_type, COUNT(*) AS count
FROM trader_promo_discounts
GROUP BY `key`, discountable_type;

SELECT promo_id, combines_with, multiple_product_discounts_per_line
FROM trader_promos;
```

The boolean backfill preserves the broad legacy allow/disallow intent, but the new policy is directional by target
type: **both** promos must allow the other's discount types. Two promos applying product discounts on the same line
additionally require `multiple_product_discounts_per_line = true` on **both** promos. Decide the policy for each
existing promo; update persisted rows deliberately after the Trader backfill instead of blindly enabling stacking.
Discounts within a single promo are handled together. Keep old promo IDs and discount IDs intact.

Check project migrations and setup/seed commands that insert into `trader_promos` directly: after the last migration
they can no longer write `is_combinable` and must supply `combines_with` (or use the Trader commands). Directly
inserted custom discounts must include `discountable_type`. Verify both a production-data upgrade and a fresh
installation with all project migrations; editing an already-run migration does not update existing rows.

## 3. Update promo commands and admin workflows

`CreateCouponPromo` is removed. Create coupon and automatic promos with `CreatePromo`: its arguments are coupon code
(`null` for automatic), start, end, `list<string> $combinesWith`, `bool $multipleProductDiscountsPerLine`, and data.
`CreateSystemPromo` now takes system promo ID, combines-with list, optional coupon code, product-stacking boolean, and
data. Use only `line`, `order`, `shipping` and `payment` in the list; decide the list from the business rules, not from
the old boolean by habit.

`UpdatePromo` now accepts promo ID, coupon code, start, end, combines-with list, product-stacking boolean, and promo
data. It **does not update discounts**. Save each discount separately via `PromoApplication`:

```php
use Thinktomorrow\Trader\Application\Promo\CUD\CreatePromoDiscount;
use Thinktomorrow\Trader\Application\Promo\CUD\UpdatePromoDiscount;

$discountId = $promoApplication->createPromoDiscount(new CreatePromoDiscount(
    $promoId->get(), 'fixed_amount', [['key' => 'minimum_amount', 'data' => ['amount' => '1000']]],
    ['amount' => '500', 'tax_mode' => 'exclusive'],
));

$promoApplication->updatePromoDiscount(new UpdatePromoDiscount(
    $promoId->get(), $discountId->get(), 'fixed_amount',
    [['key' => 'minimum_amount', 'data' => ['amount' => '1000']]],
    ['amount' => '750', 'tax_mode' => 'exclusive'],
));
```

The optional fifth argument of either discount command is a `DiscountableType` for custom target types (for example,
`DiscountableType::shipping`); built-in discounts have a fixed type. `CreatePromoDiscount` also accepts an optional
sixth argument for an explicit discount ID, useful for fixed-ID system discounts. When editing an existing discount,
pass its **persisted** ID and the complete intended conditions and data. `updatePromoDiscount()` requires that ID to
exist; do not recreate an existing discount with a new ID or repeatedly append one during an import/sync. Use
`deletePromoDiscount()` to remove an unwanted discount explicitly. `UpdateCondition` and `UpdateDiscount` are removed;
condition arrays are converted to `ConditionDTO` by the discount commands.

For an admin form that manages one discount per promo, create the promo and its discount on creation; on edit, call
`updatePromo()` for promo metadata and `updatePromoDiscount()` for its existing discount (or create one if absent).
Make imports idempotent by looking up the existing discount ID before updating. Preserve any fields or discounts that
the admin form does not manage.

## 4. Migrate custom discount integrations

The domain interfaces/factories move to `Domain\Model\Promo\Discounts\{Discount,DiscountFactory}` and
`Domain\Model\Promo\Conditions\{Condition,ConditionFactory}`. Custom domain discounts extending `BaseDiscount`
must have a resolvable `discountable_type` and implement the new `Discount` interface; `BaseDiscount` provides the
promo/discount ID accessors. Supply the type in the persisted state or override `fixedDiscountableType()` for a
truly fixed custom type.

The application layer uses `Application\Promo\ApplicableDiscounts\ApplicableDiscountFactory` and
`Application\Promo\ApplicableConditions\ApplicableConditionFactory` instead of the removed
`OrderDiscountFactory` and `OrderConditionFactory`. Built-in application discounts are now
`SalePriceProductDiscount`, `PercentageOffOrderSubtotalDiscount` and `FixedAmountOrderSubtotalDiscount`. Migrate
custom implementations of the old `LineDiscount`, `OrderDiscount` or `BaseOrderDiscount` to the relevant
`ProductDiscount`, `OrderSubtotalDiscount`, `ShippingDiscount` or `PaymentDiscount` contract and
`BaseApplicableDiscount`; their target-specific method signatures and `AppliedDiscountId` differ. Register **both**
the domain and applicable factories with built-ins and custom types. When constructing the applicable factory, supply
the applicable condition factory and `VatAllocator` as in `TraderServiceProvider`.

The order discount classes move from `Domain\Model\Order\Discount\*` to
`Domain\Model\Order\AppliedDiscount\*` (`Discount`, `AppliedDiscountId`, `DiscountableId`,
`DiscountableItem`). The `DiscountableType` enum now lives in `Domain\Model\Promo\Discounts`. Check custom order
repositories, migration helpers and tests as well as live checkout code; do not replace just the namespace of a
custom repository without checking its mapping and persisted columns.

`OrderPromoRepository`, `OrderPromo` and `ApplyPromoToOrder`/`PromoApplicationScope` are removed. Query available
promos through the domain `PromoRepository`. For catalog suggestions use `FindPromoSuggestionsForProduct` as described
in step 5; do not infer preview eligibility from the eventual winning discount. Cart recalculation uses
`AdjustDiscounts` and `SelectBestPromoCombination`, which receives
**promos**, not old discount objects; update custom cart actions accordingly. The deprecated
`AdjustOrderVatSnapshot` alias is also removed; inject `AdjustOrderPricingSnapshot`, including in quotation actions.

For custom promo previews, `PromoPreviewEligibility` and `StandardPromoPreviewEligibility` are removed. Inject
`ApplicableDiscountFactory` alongside `PromoRepository` into `FindPromoSuggestionsForProduct`; the factory translates
each domain discount with `forDiscount($promo, $discount)`. Custom `ApplicableCondition` implementations must add
`evaluatePreview(?Cart $cart, PromoPreviewProduct|Cart|ShippingProfileForCart|PaymentMethodForCart $subject): PromoPreviewStatus` for
suggestions while retaining `check(Order $order, DiscountableItem $discountable): bool` for actual discount application.
The subject prompted the suggestion and is not necessarily the target receiving the discount. A custom class
implementing `ApplicableDiscount` directly must also implement `promoPreviewStatus($subject, ?Cart $cart)`;
`BaseApplicableDiscount` provides the default. Override its `isPreviewRelevant()` for custom line discounts so they
identify the products they concern; the default does not show every line discount on every product page. For custom
shipping and payment discounts, register the matching domain and applicable discount classes before expecting cart
incentives for offered services. Replace direct calls to `cartIncentivesForProduct()` with `cartIncentivesFor()`, and
pass the quantity to add when calling `PromoPreviewProduct::fromProductDetail($product, $quantity)`.

### Known consumer work (local project versions at analysis time)

| Project | Trader version | Changes to verify in the project |
| --- | --- | --- |
| Hanolux | `0.10.3` | Migrate `Shop/Discounts` custom Odoo, pickup and Aqua'Lux discounts and the two factory bindings in `Shop/TraderServiceProvider.php`. Adapt `Admin/Models/Promo/Model/SavePromo.php`, `Tests/TraderAdminHelpers.php`, `Shop/Discounts/Create*Discount.php`, `Shop/Discounts/PickupDiscountForProduct.php`, and `Plugins/Odoo/Domain/Pricelist/Actions/SyncLoyaltyPrograms.php`. The pickup insert in `Setup/database/migrations/2026_09_24_105550_add_installer_delivery_and_pickup_discount.php` writes `is_combinable` and lacks `discountable_type`. |
| Twerk | `0.9.9` | Follow the 0.9-to-0.10 guide first. Update promo admin/helpers and custom promo factory bindings in `Shop/TraderServiceProvider.php`; update `AdjustOrderVatSnapshot` in both `Setup/Commands/MigrateToVatAllocatedOrderTotals.php` and the active `Project/Shop/Cart/ApplyQuotationShippingCost.php`. |
| Mauquoy | `0.8.1` | Follow the 0.8-to-0.9 and 0.9-to-0.10 guides first. Update promo admin/helpers and the snapshot adjuster in `Setup/Commands/MigrateToVatAllocatedOrderTotals.php`. |

All three projects also contain `Setup/Common/MysqlOrderMigrationRepository.php`, which references the removed
`Domain\Model\Order\Discount` classes. Establish whether these historical migration helpers must run again before
adapting them to the new order mapping; do not assume an unused setup command is safe to execute after the upgrade.

## 5. Set up promo previews and cache invalidation

`PromoRepository::getAvailablePromos()` includes both automatic and coupon promos that are currently online and
within their dates. `FindPromoSuggestionsForProduct::get($product, $locale, $quantity, $cart)` returns
`PromoSuggestions` with `applicablePromos` and `conditionalPromos` Collections. On the product page, `Applicable`
means adding this product meets a condition that the cart did not already meet; `Conditional` means it makes progress
toward a condition, or a newly applicable coupon still needs its code entered. `AlreadyEligible` means the cart met
the conditions before adding the product; it does **not** mean the discount was applied. `Irrelevant` means the
product makes no relevant contribution. Neither `AlreadyEligible` nor `Irrelevant` appears in the two product-page
Collections. A coupon-only order promo does not appear on the product page, but can remain a cart incentive. Preview
status does **not** guarantee the promo wins against another discount. Each suggestion exposes `promoId`, `discountId`,
`discountKey`, `pdpDescription`, `productBadgeText`, and `cartDescription`. The map key describes a discount type and
is not necessarily unique; use the persisted promo/discount IDs for identification.

Promo data can hold localized `pdp_description`, `product_badge_text` and `cart_description` maps. For each locale,
validate `pdp_description` in the shop-admin as plain text without HTML, with a maximum of 80 characters. Render it
as escaped text on the PDP; do not silently truncate it when reading promo data. Promo data can also hold the booleans
`show_on_pdp`, `show_on_cart_line`, and `show_on_cart`. The PDP flag shows suggestions with an `Applicable` or
`Conditional` preview status; `AlreadyEligible` is not shown. All visibility flags default to false. Set a coupon's
flags explicitly before exposing it to visitors. The query uses the registered `ApplicableDiscountFactory` and each
discount's `promoPreviewStatus()`. Unregistered discount types are omitted; merely setting a visibility flag does not
bypass product suitability. Built-in sale-price and order-subtotal discounts evaluate their preview conditions through
`ApplicableCondition::evaluatePreview()`. A condition missing from the applicable condition factory causes a mapping
exception, as it does for actual discount application. The cart
argument represents the cart **before** adding the product; omit it for a standalone PDP preview.
`PromoPreviewProduct` contains product/variant IDs, taxon IDs, base and sale prices, and `quantityToAdd`, not a full
`ProductDetail`. The PDP query creates this snapshot from `ProductDetail` and rejects unavailable products. Cart
incentives use stored cart-line data directly (including `sale_price_excl` and taxa), without loading product details
again; their `quantityToAdd` is zero because those lines are already included in cart totals. Do not infer a sale
from their discounted price, which may already include another promotion.
Custom `CartLine` implementations must expose `getData(?string $key = null, $default = null): mixed`; the default
cart-line implementation already provides this method.

`FindCartPromoIncentives::get($cart, $locale, $shippingProfiles = [], $paymentMethods = [])` returns one `show_on_cart`
incentive per suitable promo, excluding promos already applied anywhere in the cart (including line, order, shipping
and payment discounts). An entered coupon that did not win a discount may still be suggested. Cart incentives assess
existing variant lines and the **offered** `ShippingProfileForCart` and `PaymentMethodForCart` options passed by the
caller; they no longer read `$cart->getShipping()` or `$cart->getPayment()` as preview subjects. Shipping and payment
suggestions require registered custom discount types if no built-in discount targets that service. To show a
suggestion beneath one checkout choice, call `forShippingProfile($cart, $shippingProfile, $locale)` or
`forPaymentMethod($cart, $paymentMethod, $locale)`. These service queries include only `Applicable` and
`AlreadyEligible` suggestions with `show_on_cart = true` and a non-empty localized `cart_description`; they omit a
promo once any of its discounts is applied in the cart. An `AlreadyEligible` status describes preview eligibility,
**not** proof that a discount was actually applied. Use the cart's applied discounts for summary badges and totals.

Update custom `ApplicableDiscount::promoPreviewStatus()` and `BaseApplicableDiscount::isPreviewRelevant()` overrides
to accept `PromoPreviewProduct|Cart|ShippingProfileForCart|PaymentMethodForCart`. Update every custom
`ApplicableCondition::evaluatePreview()` signature to the same subject union. Conditions that used the *selected*
`$cart->getShipping()` or `$cart->getPayment()` to decide whether a choice qualifies should instead inspect the
offered `$subject` (for example, `$subject instanceof ShippingProfileForCart && ! $subject->requiresAddress()` for a
pickup choice). The cart argument still supplies customer, line and applied-discount context. A choice object does
not carry a calculated shipping tariff or payment cost: when a preview depends on a positive price, supply that
context in the application's choice preview rather than assuming an unselected choice has a `CartShipping` or
`CartPayment` cost. Migrate project-specific preview overrides before upgrading, as old narrower PHP method
signatures are incompatible with these new contracts. A promo with `show_on_cart = false` must be enabled in the
project before expecting a shipping or payment incentive.

`FindAppliedPromoBadgesForCartLine::get($line, $locale)` uses only actually applied line discounts and
`show_on_cart_line`; use it for cart lines and checkout summary lines. In an application using Chief or another admin,
add fields for these promo-data keys to the project's promo form and preserve existing promo data when saving. The
existing cart description is not proof of an applied discount.

### Hanolux checkout adoption

Hanolux's `CheckoutShippingController` passes each available `ShippingProfileForCart` to
`FindCartPromoIncentives::forShippingProfile($cart, $profile, $locale)` and renders the returned `cartDescription` beneath
that choice. It only requests shipping incentives for profiles with a positive nominal tariff; a shipping discount
cannot reduce an already free tariff. This is an incentive preview, not a discounted-price quote: the choice label
continues to show the tariff before promo discounts, while the applied shipping discount is shown in the checkout
summary after selection. The pickup line-discount message remains a product incentive on the pickup choice.

Update Hanolux's `Shop/Discounts/Conditions/{CustomerHasAquaLuxPricelist,IncludedTaxa,ExcludedTaxa,PickupShipping}Condition.php`
and `Shop/Discounts/{AquaLuxFreeShippingDiscount,PickupLineDiscount}.php` to accept the new preview subject union.
The Aqua'Lux discount now recognizes offered shipping profiles; `PickupShippingCondition` checks the offered profile
when it is the subject, while retaining cart-based evaluation for product previews. Aqua'Lux requires
`show_on_cart = true`: new setup uses that default, and the project migration
`2026_11_24_220402_enable_aqualux_shipping_incentive.php` enables it for existing rows once. Subsequent admin changes
remain authoritative because the setup action preserves stored promo data. Keep the `show_on_cart` check in Trader's
preview query; hiding a promo in Chief must also hide its shipping incentive.

The default Laravel binding of `PromoRepository` is now `MemoizedMysqlPromoRepository`, backed by `PromoCache`.
Available promo reads are shared for up to 60 minutes, with an earlier refresh at the next scheduled start or end.
`find()` bypasses the cache for mutable promo editing. `save()` and `delete()` invalidate after a successful write
(after commit if inside a transaction); reads inside a transaction bypass the cache. Do not mutate objects returned
by the available-promo listing. Any project-specific Eloquent/DB updates to `trader_promos`, its discounts, or
discount conditions bypass repository invalidation: migrate them to `PromoApplication`, or inject `PromoCache` and
call `clear()` **after commit**. In particular, audit promo admin forms, imports, migrations and setup commands.
No new promo table columns are required for the presentation keys.

## 6. Verify before release

The selector compares compatible promo sets by final **VAT-inclusive order total**. It applies product discounts,
reprices shipping and payment, then applies order, shipping and payment discounts. Check selection and totals for
non-combinable coupons, multiple automatic promos, overlapping product discounts, mixed VAT rates, customer-specific
prices, free shipping and pickup; compare against the intended legacy behavior, not just the lowest price.

On a production-data copy, verify promo hydration and admin create/edit/delete, rerunnable sync/setup commands,
cart refresh and checkout, and historical confirmed/paid orders against invoices and exports. Existing frozen orders
must retain their totals. Run relevant project tests and static analysis, then repeat the code search in step 1 for
obsolete API usage. If upgrading across several Trader releases, include the earlier guide's pricing checks too.
