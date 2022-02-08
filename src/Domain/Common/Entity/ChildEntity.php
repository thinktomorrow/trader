<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Entity;

interface ChildEntity
{
    public function getMappedData(): array;

    public static function fromMappedData(array $state, array $aggregateState): static;
}
