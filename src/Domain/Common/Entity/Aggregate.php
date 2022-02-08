<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Entity;

interface Aggregate
{
    public function getMappedData(): array;

    public function getChildEntities(): array;

    public static function fromMappedData(array $state, array $childEntities = []): static;
}
