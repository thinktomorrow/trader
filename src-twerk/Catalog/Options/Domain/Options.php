<?php

namespace Thinktomorrow\Trader\Catalog\Options\Domain;

interface Options
{
    public function getIds(): array;

    public function findById(string $id): Option;

    /** Options grouped by shop option */
    public function grouped(): array;

    public function getIterator();
}
