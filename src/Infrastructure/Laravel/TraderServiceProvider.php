<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel;

use Illuminate\Support\ServiceProvider;
use Thinktomorrow\Trader\Domain\Model\Order\OrderRepository;

class TraderServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(OrderRepository::class, MysqlOrderRepository::class);
    }

    public function boot()
    {

    }
}
