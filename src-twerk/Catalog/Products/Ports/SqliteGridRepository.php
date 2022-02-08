<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Catalog\Products\Ports;

use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;
use Thinktomorrow\Trader\Catalog\Products\Domain\GridRepository;

class SqliteGridRepository extends MysqlGridRepository
{
    protected function addSortByLabel($order = 'ASC'): GridRepository
    {
        $labelField = 'LOWER(json_extract('.static::$productTable.'.data, "$.title.'.$this->context->getLocale()->getLanguage().'"))';

        $this->builder->addSelect(
            DB::raw($this->grammarGroupConcat($labelField) . ' AS labels'),
        );

        $this->builder->orderBy('labels', $order);

        return $this;
    }

    /**
     * Group Concat grammar for sqlite
     *
     * @param string $column
     * @param string $separator
     * @return \Illuminate\Database\Query\Expression
     */
    protected function grammarGroupConcat(string $column, string $separator = ','): Expression
    {
        return DB::raw('GROUP_CONCAT('.$column.',"'.$separator.'")');
    }
}
