<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Catalog\Products\Domain;

use Thinktomorrow\Trader\Common\State\StateValueDefaults;

class ProductGroupState
{
    use StateValueDefaults;

    public static $KEY = 'state';

    const DRAFT = 'draft'; // productgroup is offline and in concept
    const ONLINE = 'online'; // online
    const ARCHIVED = 'archived'; // archived and optionally replaced by new product
}
