## Trader
This package provides domain-first ecommerce business logic for Laravel projects, including catalog management, taxonomies, cart and checkout flows, order lifecycle state transitions, VAT/price calculations, promotions, shipping profiles, payment methods, stock handling, and customer account flows.
### Features
- Domain-first ecommerce logic: use Trader as the source of truth for business rules around products, carts, orders, VAT, and discounts.
- Application-service workflow: perform business actions through `Thinktomorrow\Trader\Application\*` services and command DTOs.
- Upgrade-safe integration: extend behavior in app code and never edit `vendor/thinktomorrow/trader` directly.
- Event-driven extensibility: hook project-specific side effects into Trader domain events via Laravel listeners/jobs.
- Config-driven pricing & VAT behavior: align all checkout and total calculations with `config/trader.php`. Example usage:
@verbatim
    <code-snippet name="Install and initialize Trader" lang="bash">
        composer require thinktomorrow/trader
        php artisan vendor:publish --provider="Thinktomorrow\Trader\Infrastructure\Laravel\TraderServiceProvider"
        php artisan migrate
    </code-snippet>
@endverbatim
### Development Guidelines
- Prefer extension over modification; reuse existing Trader APIs and conventions before introducing abstractions.
- Use Trader Application services as entry points; avoid direct persistence writes for business mutations.
- Respect package boundaries: keep framework concerns in `Infrastructure`, orchestration in `Application`, and business invariants in `Domain`.
- Preserve state-machine semantics by using Trader order/payment/shipping transition APIs instead of manual state updates.
- Keep VAT/price logic consistent with config keys like `does_price_input_includes_vat`, `include_vat_in_prices`, `calculate_item_discounts_excluding_vat`, `vat_rounding_strategy`, and `allow_vat_exemption`.
- Keep app-specific behavior in the host app, not inside package/vendor code.
- Avoid breaking public behavior; prefer additive, backwards-compatible defaults.
- Do not create new top-level folders or add dependencies without explicit approval.
- Do not commit secrets or `.env` values.
### Testing & Verification
- Add or update focused tests for each changed behavior.
- Prefer domain-focused unit tests for invariants and acceptance/infrastructure tests for application/integration behavior.
- Run the smallest relevant suite first, then broader suites when confidence increases.
- For cart/checkout/order changes, explicitly verify totals, VAT behavior, discounts, and state transitions.
- Run lint/format/static-analysis tools relevant to edited files.
@verbatim
    <code-snippet name="Run Trader quality checks" lang="bash">
        vendor/bin/phpunit tests/Unit
        vendor/bin/phpunit tests/Acceptance
        vendor/bin/phpunit tests/Infrastructure
        vendor/bin/phpstan analyse src --level=2
        vendor/bin/deptrac analyse
    </code-snippet>
@endverbatim
