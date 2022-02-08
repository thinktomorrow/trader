<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Catalog\Options\Domain;

interface Option
{
    public function getId(): string;

    public function getProductGroupId(): string;

    public function getOptionTypeId(): string;

    public function getValues(): array;

    public function getValue(string $index): ?string;

    public function getLabel(string $index): ?string;
}
