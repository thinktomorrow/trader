<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Migrations;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Infrastructure\TestCase;

final class PromoCombinationPolicyMigrationTest extends TestCase
{
    public function test_it_backfills_legacy_values_without_overwriting_policies_and_drops_the_old_column(): void
    {
        $migration = require dirname(__DIR__, 3).'/src/Infrastructure/Laravel/database/migrations/2026_11_10_000002_remove_legacy_promo_combinability.php';
        $migration->down();

        foreach ([
            ['promo_id' => 'legacy-no', 'is_combinable' => false, 'combines_with' => null],
            ['promo_id' => 'legacy-yes', 'is_combinable' => true, 'combines_with' => null],
            ['promo_id' => 'custom', 'is_combinable' => false, 'combines_with' => '["shipping"]'],
        ] as $promo) {
            DB::table('trader_promos')->insert(array_merge([
                'state' => 'online', 'is_system_promo' => false, 'coupon_code' => null,
            ], $promo));
        }

        $backfill = require dirname(__DIR__, 3).'/src/Infrastructure/Laravel/database/migrations/2026_11_10_000001_backfill_promo_combination_policy.php';
        $backfill->up();

        $this->assertSame(['shipping'], json_decode(DB::table('trader_promos')->where('promo_id', 'custom')->value('combines_with'), true));

        // The final migration also handles rows added between backfill and deployment.
        DB::table('trader_promos')->where('promo_id', 'legacy-yes')->update(['combines_with' => null]);

        $migration->up();

        $this->assertFalse(Schema::hasColumn('trader_promos', 'is_combinable'));
        $this->assertSame([], json_decode(DB::table('trader_promos')->where('promo_id', 'legacy-no')->value('combines_with'), true));
        $this->assertSame(['line', 'order', 'shipping', 'payment'], json_decode(DB::table('trader_promos')->where('promo_id', 'legacy-yes')->value('combines_with'), true));
        $this->assertSame(['shipping'], json_decode(DB::table('trader_promos')->where('promo_id', 'custom')->value('combines_with'), true));

        $migration->down();

        $this->assertSame(0, (int) DB::table('trader_promos')->where('promo_id', 'legacy-no')->value('is_combinable'));
        $this->assertSame(1, (int) DB::table('trader_promos')->where('promo_id', 'legacy-yes')->value('is_combinable'));
        $this->assertSame(1, (int) DB::table('trader_promos')->where('promo_id', 'custom')->value('is_combinable'));

        $migration->up();
    }
}
