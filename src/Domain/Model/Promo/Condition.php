<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Promo;

use Thinktomorrow\Trader\Domain\Common\Entity\ChildEntity;
use Thinktomorrow\Trader\Domain\Common\Map\Mappable;

interface Condition extends ChildEntity, Mappable
{
}
