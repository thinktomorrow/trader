<?php declare(strict_types=1);

namespace Thinktomorrow\Trader\Purchase\Notes\Domain;

interface Note
{
    /**
     * Tag this note with a specific tag(s). This allows for the client
     * to choose when to render this note.
     * @param mixed ...$tags
     * @return $this
     */
    public function tag(...$tags): self;

    public function render(string $locale, array $whitelistedTags = []): string;
}
