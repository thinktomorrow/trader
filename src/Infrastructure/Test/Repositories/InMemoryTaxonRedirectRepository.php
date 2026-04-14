<?php

namespace Thinktomorrow\Trader\Infrastructure\Test\Repositories;

use Thinktomorrow\Trader\Application\Taxon\Redirect\Redirect;
use Thinktomorrow\Trader\Application\Taxon\Redirect\TaxonRedirectRepository;
use Thinktomorrow\Trader\Domain\Common\Locale;

final class InMemoryTaxonRedirectRepository implements TaxonRedirectRepository
{
    /** @var Redirect[] */
    private array $items = [];

    private int $autoIncrement = 1;

    public function find(Locale $locale, string $from): ?Redirect
    {
        $from = self::sanitizeSlug($from);

        foreach ($this->items as $redirect) {
            if ($redirect->getLocale()->equals($locale) && $redirect->getFrom() === $from) {
                return $redirect;
            }
        }

        return null;
    }

    /** @return Redirect[] */
    public function getAllTo(Locale $locale, string $to): array
    {
        $to = self::sanitizeSlug($to);

        return array_values(array_filter(
            $this->items,
            fn (Redirect $redirect) => $redirect->getLocale()->equals($locale) && $redirect->getTo() === $to
        ));
    }

    public function save(Redirect $redirect): void
    {
        $from = self::sanitizeSlug($redirect->getFrom());
        $to = self::sanitizeSlug($redirect->getTo());

        // Update other redirects that pointed to this "from"
        foreach ($this->getAllTo($redirect->getLocale(), $from) as $existingRedirect) {
            if ($existingRedirect->getFrom() === $to) {
                $this->delete($existingRedirect);

                continue;
            }

            $this->save($existingRedirect->changeTo($to));
        }

        if ($redirect->getId()) {
            // update
            foreach ($this->items as $key => $item) {
                if ($item->getId() === $redirect->getId()) {
                    $this->items[$key] = $redirect;

                    return;
                }
            }
        } else {
            // insert
            $id = (string) $this->autoIncrement++;
            $newRedirect = new Redirect(
                $redirect->getLocale(),
                $from,
                $to,
                $id,
                new \DateTime
            );
            $this->items[] = $newRedirect;
        }
    }

    public function delete(Redirect $redirect): void
    {
        if (! $redirect->getId()) {
            return;
        }

        $this->items = array_values(array_filter(
            $this->items,
            fn (Redirect $item) => $item->getId() !== $redirect->getId()
        ));
    }

    private static function sanitizeSlug(string $slug): string
    {
        return trim($slug, '/ ');
    }
}
