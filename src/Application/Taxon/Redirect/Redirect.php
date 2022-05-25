<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Taxon\Redirect;

class Redirect
{
    private string $from;
    private string $to;
    private ?string $id;
    private ?\DateTime $created_at;

    public function __construct(string $from, string $to, ?string $id = null, ?\DateTime $created_at = null)
    {
        $this->from = $from;
        $this->to = $to;
        $this->id = $id;
        $this->created_at = $created_at;
    }

    public function changeTo(string $to): static
    {
        $this->to = $to;

        return $this;
    }

    public function getFrom(): string
    {
        return $this->from;
    }

    public function getTo(): string
    {
        return $this->to;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->created_at;
    }
}
