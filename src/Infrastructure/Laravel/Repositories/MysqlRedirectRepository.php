<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Illuminate\Support\Facades\DB;
use Thinktomorrow\Trader\Application\Taxon\Redirect\Redirect;
use Thinktomorrow\Trader\Application\Taxon\Redirect\RedirectRepository;
use Thinktomorrow\Trader\Domain\Common\Locale;

class MysqlRedirectRepository implements RedirectRepository
{
    private static string $redirectTable = 'trader_taxa_redirects';

    public function find(Locale $locale, string $from): ?Redirect
    {
        $result = DB::table(static::$redirectTable)
            ->where('locale', $locale->get())
            ->where('from', static::sanitizeSlug($from))
            ->first();

        if (! $result) {
            return null;
        }

        return new Redirect($locale, $result->from, $result->to, (string) $result->id, \DateTime::createFromFormat('Y-m-d H:i:s', $result->created_at));
    }

    public function getAllTo(Locale $locale, string $to): array
    {
        return DB::table(static::$redirectTable)
            ->where('locale', $locale->get())
            ->where('to', static::sanitizeSlug($to))
            ->get()
            ->map(fn ($result) => new Redirect($locale, $result->from, $result->to, (string) $result->id, \DateTime::createFromFormat('Y-m-d H:i:s', $result->created_at)))
            ->toArray();
    }

    public function save(Redirect $redirect): void
    {
        $from = static::sanitizeSlug($redirect->getFrom());
        $to = static::sanitizeSlug($redirect->getTo());

        /**
         * If there are any existing redirects with this 'from' as its 'to' target,
         * we'll update those as well to reflect the new target
         */
        foreach ($this->getAllTo($redirect->getLocale(), $from) as $existingRedirect) {
            // If the from and to are the same, we'll remove the record
            if ($existingRedirect->getFrom() == $to) {
                $this->delete($existingRedirect);

                continue;
            }

            $this->save($existingRedirect->changeTo($to));
        }

        if ($redirect->getId()) {
            DB::table(static::$redirectTable)->where('id', $redirect->getId())->update([
                'from' => static::sanitizeSlug($redirect->getFrom()),
                'to' => static::sanitizeSlug($redirect->getTo()),
            ]);
        } else {
            DB::table(static::$redirectTable)->insert([
                'locale' => $redirect->getLocale()->get(),
                'from' => static::sanitizeSlug($redirect->getFrom()),
                'to' => static::sanitizeSlug($redirect->getTo()),
                'created_at' => new \DateTime(),
            ]);
        }
    }

    public function delete(Redirect $redirect): void
    {
        if (! $redirect->getId()) {
            return;
        }

        DB::table(static::$redirectTable)->where('id', $redirect->getId())->delete();
    }

    private static function sanitizeSlug(string $slug): string
    {
        return trim($slug, '/ ');
    }
}
