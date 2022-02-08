<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Catalog\Taxa\Domain;

use Thinktomorrow\Vine\Node;

interface Taxon extends Node
{
    public function getId(): string;

    public function getKey(): string;

    public function getLabel(): string;

    public function showOnline(): bool;
}
