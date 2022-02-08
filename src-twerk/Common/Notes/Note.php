<?php declare(strict_types=1);

namespace Thinktomorrow\Trader\Common\Notes;

use Thinktomorrow\Trader\Common\Domain\Locale;

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

    public function get(Locale $locale, array $whitelistedTags = []): string;
}
