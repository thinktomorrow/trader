## Thinktomorrow Trader
Thinktomorrow Trader is a domain-first ecommerce package for Laravel.
Use Trader's Application layer to change behavior, keep Domain invariants intact, and treat Infrastructure classes as implementation details.
### Installation & baseline setup
- Install package: `composer require thinktomorrow/trader`
- Trader service providers are auto-discovered (`TraderServiceProvider` and `ShopServiceProvider`).
- Publish config when needed: `php artisan vendor:publish --provider="Thinktomorrow\Trader\Infrastructure\Laravel\TraderServiceProvider"`
- Run migrations: `php artisan migrate`
- Trader expects a valid `config/trader.php` for currency, locale, VAT country, and pricing/VAT behavior.
- This package is typically used with Chief for admin/catalog management.
### Core conventions
- Prefer `Thinktomorrow\Trader\Application\*` services as entry points for all business actions.
- Pass command/DTO objects (e.g. `CreateProduct`, `AddLine`, `PayOrder`) to Application services.
- Do not bypass domain logic by writing directly to Trader database tables.
- Keep custom project logic in your app layer (controllers/actions/listeners), not by patching package internals.
- IDs are value objects (`fromString()` / `get()`), not raw strings deep in domain logic.
- Domain events are dispatched through Laravel's event dispatcher; integrate with listeners/jobs.
### Main application services
- Catalog: `ProductApplication`, `TaxonApplication`, `TaxonomyApplication`, `StockApplication`
- Checkout/cart: `CartApplication`, `CouponPromoApplication`, `VatNumberApplication`, `VatExemptionApplication`
- Order lifecycle: `OrderStateApplication`, `MerchantOrderApplication`
- Supporting domains: `ShippingProfileApplication`, `PaymentMethodApplication`, `VatRateApplication`, `CustomerApplication`
### Configuration that materially changes behavior
- `currency`, `locale`
- `primary_vat_country`, `fallback_standard_vat_rate`, `allow_vat_exemption`
- `does_price_input_includes_vat`, `include_vat_in_prices`, `calculate_item_discounts_excluding_vat`
- `vat_rounding_strategy` (`unit_based` or `line_based`)
- `show_variants_in_grid_by_default`
When changing VAT or price-input semantics in an existing project, verify historical data assumptions and checkout totals.
### Customer auth routes & guard
- Trader's Shop provider loads default customer auth routes under `/you/*`.
- It registers middleware aliases: `customer-auth`, `customer-guest`, `customer-verified`, `customer-signed`.
- It configures `auth.providers.customer`, `auth.guards.customer`, and `auth.passwords.customer`.
If a project has custom auth UX, keep route/guard behavior explicit and avoid breaking existing customer session flows.
### Events integration
Listen to Trader domain events for side effects (mailing, ERP sync, analytics, webhooks).
Examples include order, product, stock, promo, taxon, taxonomy, customer, and VAT-related events.
@verbatim
    <code-snippet name="Listen to Trader domain events" lang="php">
        use Illuminate\Support\Facades\Event;
        use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStateUpdated;
        Event::listen(OrderStateUpdated::class, function (OrderStateUpdated $event) {
        // Trigger project-specific side effects here.
        });
    </code-snippet>
@endverbatim
### Example usage patterns
@verbatim
    <code-snippet name="Create product through Application layer" lang="php">
        use Thinktomorrow\Trader\Application\Product\CreateProduct;
        use Thinktomorrow\Trader\Application\Product\ProductApplication;
        $productId = app(ProductApplication::class)->createProduct(
        new CreateProduct(
        taxonIds: ['
        <taxon-uuid>'],
            unitPrice: '49.95',
            taxRate: '21',
            sku: 'SKU-001',
            data: ['title' => ['en' => 'Coffee mug']],
            variantData: []
            )
            );
    </code-snippet>
@endverbatim
@verbatim
    <code-snippet name="Add cart line through CartApplication" lang="php">
        use Thinktomorrow\Trader\Application\Cart\CartApplication;
        use Thinktomorrow\Trader\Application\Cart\Line\AddLine;
        app(CartApplication::class)->addLine(
        new AddLine(
        orderId: $orderId,
        variantId: $variantId,
        quantity: 2,
        personalisations: [],
        data: ['locale' => app()->getLocale()]
        )
        );
    </code-snippet>
@endverbatim
@verbatim
    <code-snippet name="Apply order state transition" lang="php">
        use Thinktomorrow\Trader\Application\Order\State\Order\PayOrder;
        use Thinktomorrow\Trader\Application\Order\State\OrderStateApplication;
        app(OrderStateApplication::class)->payOrder(
        new PayOrder($orderId, ['source' => 'checkout'])
        );
    </code-snippet>
@endverbatim
### Guidance for AI agents
- Start with existing project patterns and Trader Application services before introducing abstractions.
- Prefer composing listeners/actions around Trader events over modifying Trader internals.
- Keep VAT, price display, and discount calculations consistent with current config.
- When upgrading Trader, check migration/config deltas and apply forward-only app migrations when needed.
- Add or update tests for cart totals, order state transitions, VAT behavior, and event dispatch side effects.
If you want, I can also generate a second guideline file focused only on “using Trader in host apps” (shorter, project-facing tone) for .ai/guidelines/trader.md in consuming applications.
