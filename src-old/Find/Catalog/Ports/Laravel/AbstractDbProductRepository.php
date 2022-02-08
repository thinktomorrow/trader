<?php

namespace Find\Catalog\Ports\Laravel;

use Find\Channels\ChannelId;
use Illuminate\Support\Facades\DB;
use Common\Domain\Locales\LocaleId;

abstract class AbstractDbProductRepository
{
    protected function initBuilder(ChannelId $channelId, LocaleId $localeId)
    {
        $tableName = $this->tableName($channelId->get());

        // TODO need better query where missing locale record should not return empty sql result
        return DB::table($tableName)
//            ->leftjoin('product_read_translations', $tableName . '.id', '=', 'product_read_translations.product_id')
//            ->where('product_read_translations.locale', $localeId->get())
            ->select($tableName . '.*');
    }

    protected function tableName(string $channel): string
    {
        return "product_reads_$channel";
    }


}
