<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Catalog\Products\Domain;

use Thinktomorrow\Trader\Common\State\StateValueDefaults;

class ProductState
{
    use StateValueDefaults;

    public static $KEY = 'state';

    const AVAILABLE = 'available'; // product is available for purchase
    const UNAVAILABLE = 'unavailable'; // product is not available for purchase
    const DELETED = 'deleted'; // Product is / will be deleted
}
