<?php declare(strict_types=1);

namespace Common\Notes;

use Common\Domain\Locales\LocaleId;

interface Note
{
    /**
     * Tag this note with a specific tag(s). This allows for the client
     * to choose when to render this note.
     *
     * @param array $tags
     * @return $this
     */
    public function tag(array $tags): Note;

    public function render(LocaleId $localeId, array $whitelistedTags = []): string;
}
