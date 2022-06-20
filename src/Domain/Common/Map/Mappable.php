<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Map;

interface Mappable
{
    public static function getMapKey(): string;
}
