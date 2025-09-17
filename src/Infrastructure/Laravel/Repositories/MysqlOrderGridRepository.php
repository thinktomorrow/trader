<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Psr\Container\ContainerInterface;
use Thinktomorrow\Trader\Application\Order\Grid\OrderGridItem;
use Thinktomorrow\Trader\Application\Order\Grid\OrderGridRepository;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\TraderConfig;

final class MysqlOrderGridRepository implements OrderGridRepository
{
    private ContainerInterface $container;
    private TraderConfig $traderConfig;

    private int $perPage = 20;
    private Locale $locale;
    protected Builder $builder;

    private static string $orderTable = 'trader_orders';
    private static string $shopperTable = 'trader_order_shoppers';

    public function __construct(ContainerInterface $container, TraderConfig $traderConfig)
    {
        $this->container = $container;
        $this->traderConfig = $traderConfig;
        $this->locale = $traderConfig->getDefaultLocale();

        // Basic builder query
        $this->builder = DB::table(static::$orderTable)
            ->leftJoin(static::$shopperTable, static::$orderTable . '.order_id', '=', static::$shopperTable . '.order_id')
            ->select([
                static::$orderTable . '.*',
                static::$shopperTable . '.email AS shopper_email',
                static::$shopperTable . '.is_business AS is_business',
                static::$shopperTable . '.data AS shopper_data',
                static::$shopperTable . '.customer_id AS shopper_customer_id',
            ]);
    }

    public function filterByOrderReference(string $orderReference): static
    {
        $this->builder->where('order_ref', 'LIKE', '%' . $orderReference . '%');

        return $this;
    }

    public function filterByShopperEmail(string $shopperEmail): static
    {
        $this->builder->where(static::$shopperTable . '.email', 'LIKE', '%' . $shopperEmail . '%');

        return $this;
    }

    public function filterByShopperTerm(string $shopperTerm): static
    {
        $this->builder->where(function ($query) use ($shopperTerm) {
            $query->where(static::$shopperTable . '.email', 'LIKE', '%' . $shopperTerm . '%')
                ->orWhereRaw("JSON_SEARCH(LOWER(`trader_order_shoppers`.`data`), 'all', ?) IS NOT NULL", ["%" . strtolower($shopperTerm) . "%"]);
        });

        return $this;
    }

    public function filterByCustomerId(string $customerId): static
    {
        $this->builder->where(static::$shopperTable . '.customer_id', $customerId);

        return $this;
    }

    public function filterByStates(array $states): static
    {
        $this->builder->whereIn(static::$orderTable . '.order_state', $states);

        return $this;
    }

    public function filterByConfirmedAt(?string $startAt = null, ?string $endAt = null): static
    {
        return $this->filterByDate('confirmed_at', $startAt, $endAt);
    }

    public function filterByDeliveredAt(?string $startAt = null, ?string $endAt = null): static
    {
        return $this->filterByDate('delivered_at', $startAt, $endAt);
    }

    private function filterByDate(string $column, string $startAt = null, string $endAt = null): static
    {
        if (! is_null($startAt)) {
            $this->builder->where(static::$orderTable . '.' . $column, '>=', Carbon::parse($startAt)->toDateTimeString());
        }

        if (! is_null($endAt)) {
            $this->builder->where(static::$orderTable . '.' . $column, '<=', Carbon::parse($endAt)->toDateTimeString());
        }

        return $this;
    }

    public function sortByCreatedAt(): static
    {
        $this->builder->orderBy('created_at', 'ASC');

        return $this;
    }

    public function sortByCreatedAtDesc(): static
    {
        $this->builder->orderBy('created_at', 'DESC');

        return $this;
    }

    public function sortByConfirmedAt(): static
    {
        $this->builder->orderBy('confirmed_at', 'ASC');

        return $this;
    }

    public function sortByConfirmedAtDesc(): static
    {
        $this->builder->orderBy('confirmed_at', 'DESC');

        return $this;
    }

    public function sortByDeliveredAt(): static
    {
        $this->builder->orderBy('delivered_at', 'ASC');

        return $this;
    }

    public function sortByDeliveredAtDesc(): static
    {
        $this->builder->orderBy('delivered_at', 'DESC');

        return $this;
    }

    public function paginate(int $perPage): static
    {
        $this->perPage = $perPage;

        return $this;
    }

    public function limit(int $limit): static
    {
        $this->builder->limit($limit);

        return $this;
    }

    public function setLocale(Locale $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function getResults(): LengthAwarePaginator
    {
        // Default ordering if no ordering has been applied yet.
        if (! $this->builder->orders || count($this->builder->orders) < 1) {
            $this->sortByDefault();
        }

        $results = $this->builder->paginate($this->perPage)->withQueryString();

        return $results->setCollection(
            $results->getCollection()
                ->map(fn ($state) => get_object_vars($state))
                ->map(fn ($state) => $this->container->get(OrderGridItem::class)::fromMappedData($state, [
                    'email' => $state['shopper_email'],
                    'is_business' => $state['is_business'],
                    'data' => $state['shopper_data'],
                    'customer_id' => $state['shopper_customer_id'],
                ]))
        );
    }

    public function getOrderIds(): array
    {
        // Default ordering if no ordering has been applied yet.
        if (! $this->builder->orders || count($this->builder->orders) < 1) {
            $this->sortByDefault();
        }

        return $this->builder->select(static::$orderTable . '.order_id')->get()->pluck('order_id')->toArray();
    }

    private function sortByDefault(): void
    {
        $this->builder->orderBy(static::$orderTable . '.created_at', 'DESC');
    }
}
