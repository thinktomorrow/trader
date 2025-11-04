<?php

namespace Thinktomorrow\Trader\Domain\Model\Order\Line;

class PurchasableReference
{
    private string $type;

    protected string $id;

    public function __construct(string $type, string $id)
    {
        $this->type = $type;
        $this->id = $id;
    }

    public static function fromString(string $reference): self
    {
        if (strpos($reference, '@') == false) {
            throw new \InvalidArgumentException('Invalid reference composition. A Purchasable reference should consist of schema <class>@<id>. [' . $reference . '] was passed instead.');
        }

        [$type, $id] = explode('@', $reference);

        if ($id === '') {
            throw new \InvalidArgumentException('Missing id on purchasable reference. [' . $reference . '] was passed.');
        }

        return new self($type, $id);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function get(): string
    {
        return "$this->type@$this->id";
    }

    public function equals($other): bool
    {
        return get_class($this) === get_class($other) && $this->get() === $other->get();
    }

    public function is(string $modelReferenceString): bool
    {
        return $this->get() === $modelReferenceString;
    }

    public function __toString(): string
    {
        return $this->get();
    }
}
