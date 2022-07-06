<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\CUD;

use Thinktomorrow\Trader\Domain\Model\Promo\DiscountId;

class UpdateDiscount
{
    private ?string $discountId;
    private string $key;
    private array $data;
    private array $conditions;

    public function __construct(?string $discountId, string $key, array $conditions, array $data)
    {
        $this->discountId = $discountId;
        $this->key = $key;
        $this->data = $data;
        $this->conditions = $conditions;
    }

    public function getDiscountId(): ?DiscountId
    {
        return $this->discountId ? DiscountId::fromString($this->discountId) : null;
    }

    public function getMapKey(): string
    {
        return $this->key;
    }

    /** @return UpdateCondition[] */
    public function getConditions(): array
    {
        return array_map(fn (array $conditionPayload) => new UpdateCondition(...$conditionPayload), $this->conditions);
    }

    public function getData(): array
    {
        return $this->data;
    }
}
