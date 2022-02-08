<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Seo;

use Illuminate\Database\Eloquent\Model;

class Redirect extends Model
{
    public $table = 'trader_redirects';
    public $timestamps = false;
    public $guarded = ['id'];

    public static function from(string $from)
    {
        return static::where('from', static::sanitizeSlug($from))->first();
    }

    public static function allTo(string $to)
    {
        return static::where('to', static::sanitizeSlug($to))->get();
    }

    public static function add(string $from, string $to): void
    {
        $from = static::sanitizeSlug($from);
        $to = static::sanitizeSlug($to);

        // If there are any existing redirects with this 'from' as its 'to' target, we'll update those as well to reflect the new up to date target
        foreach (static::allTo($from) as $existingRedirect) {

            // If the from and to are the same, we'll remove the record
            if ($existingRedirect->from == $to) {
                $existingRedirect->delete();

                continue;
            }

            $existingRedirect->update(['to' => $to]);
        }

        static::create([
            'from' => $from,
            'to' => $to,
        ]);
    }

    private static function sanitizeSlug(string $slug): string
    {
        return trim($slug, '/ ');
    }
}
