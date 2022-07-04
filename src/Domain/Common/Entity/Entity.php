<?php

namespace Thinktomorrow\Trader\Domain\Common\Entity;

interface Entity
{
    public function getMappedData(): array;

    public static function fromMappedData(array $state): static;
}
