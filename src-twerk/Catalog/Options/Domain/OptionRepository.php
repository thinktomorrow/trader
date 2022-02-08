<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Catalog\Options\Domain;

interface OptionRepository
{
    public function get(string $productGroupId): Options;

    public function create(array $values): Option;

    public function save(string $productGroupOptionId, array $values): void;

    public function delete(string $productGroupOptionId): void;
}
