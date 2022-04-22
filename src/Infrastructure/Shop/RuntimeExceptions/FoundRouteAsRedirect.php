<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Shop\RuntimeExceptions;

class FoundRouteAsRedirect extends \RuntimeException
{
    private string $redirect;

    public function setRedirect(string $redirect): static
    {
        $this->redirect = $redirect;

        return $this;
    }

    public function getRedirect(): string
    {
        return $this->redirect;
    }
}
