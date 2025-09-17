<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Order\Grid;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Thinktomorrow\Trader\Domain\Common\Locale;

interface OrderGridRepository
{
    public function filterByOrderReference(string $orderReference): static;

    public function filterByShopperEmail(string $shopperEmail): static;

    public function filterByShopperTerm(string $shopperTerm): static;

    public function filterByCustomerId(string $customerId): static;

    public function filterByStates(array $states): static;

    public function filterByConfirmedAt(?string $startAt = null, ?string $endAt = null): static;

    public function filterByDeliveredAt(?string $startAt = null, ?string $endAt = null): static;

    public function sortByCreatedAt(): static;

    public function sortByCreatedAtDesc(): static;

    public function sortByConfirmedAt(): static;

    public function sortByConfirmedAtDesc(): static;

    public function sortByDeliveredAt(): static;

    public function sortByDeliveredAtDesc(): static;

    public function paginate(int $perPage): static;

    public function limit(int $limit): static;

    public function setLocale(Locale $locale): static;

    public function getResults(): LengthAwarePaginator;

    public function getOrderIds(): array;
}
