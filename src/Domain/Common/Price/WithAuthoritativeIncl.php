<?php

namespace Thinktomorrow\Trader\Domain\Common\Price;

trait WithAuthoritativeIncl
{
    protected bool $authoritativeIncl = false;

    protected function authoritativeIncl(): bool
    {
        return $this->authoritativeIncl;
    }

    protected function setAuthoritativeIncl(bool $authoritativeIncl): void
    {
        $this->authoritativeIncl = $authoritativeIncl;
    }
}
