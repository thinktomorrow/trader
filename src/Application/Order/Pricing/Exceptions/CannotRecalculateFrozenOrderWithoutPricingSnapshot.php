<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Order\Pricing\Exceptions;

final class CannotRecalculateFrozenOrderWithoutPricingSnapshot extends \LogicException {}
