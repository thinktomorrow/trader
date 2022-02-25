<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfile;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileRepository;

class MysqlShippingProfileRepository implements ShippingProfileRepository
{
    public function __construct()
    {

    }

    public function save(ShippingProfile $shippingProfile): void
    {
        // TODO: Implement save() method.
    }

    public function find(ShippingProfileId $shippingProfileId): ShippingProfile
    {
        // TODO: Implement find() method.
    }

    public function delete(ShippingProfileId $shippingProfileId): void
    {
        // TODO: Implement delete() method.
    }

    public function nextReference(): ShippingProfileId
    {
        // TODO: Implement nextReference() method.
    }
}
