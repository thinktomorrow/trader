<?php

namespace Thinktomorrow\Trader\Application\Taxon\Redirect;

interface RedirectRepository
{
    public function find(string $from): ?Redirect;

    public function getAllTo(string $to): array;

    public function save(Redirect $redirect): void;

    public function delete(Redirect $redirect): void;
}
