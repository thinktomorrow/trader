<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\CUD;

use Thinktomorrow\Trader\Domain\Model\Promo\PromoId;

class CreateSystemPromo
{
    private string $systemPromoId;

    protected bool $isCombinable;

    protected array $data;

    public function __construct(string $systemPromoId, bool $isCombinable, array $data)
    {
        $this->systemPromoId = $systemPromoId;
        $this->isCombinable = $isCombinable;
        $this->data = $data;
    }

    public function getSystemPromoId(): PromoId
    {
        return PromoId::fromString($this->systemPromoId);
    }

    public function isCombinable(): bool
    {
        return $this->isCombinable;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
