<?php

namespace Haikara\SequelBuilder\Builder;

use DateTimeInterface;
use Haikara\SequelBuilder\CaseStatement;
use Haikara\SequelBuilder\Raw;
use Haikara\SequelBuilder\Rules\EveryRules;

class SQL
{
    public static function select(string $table_name, ?string $alias = null): Select
    {
        return (new Select)->table($table_name, $alias);
    }

    public static function insert(string $table_name, ?string $alias = null): Insert
    {
        return (new Insert)->table($table_name, $alias);
    }

    public static function update(string $table_name, ?string $alias = null): Update
    {
        return (new Update)->table($table_name, $alias);
    }

    public static function delete(string $table_name, ?string $alias = null): Delete
    {
        return (new Delete)->table($table_name, $alias);
    }

    public static function raw(string|int|float|bool|null $query): Raw
    {
        return new Raw($query ?? 'NULL');
    }

    public static function case(mixed $compare_target = null): CaseStatement
    {
        return new CaseStatement($compare_target);
    }

    public static function rules(): EveryRules
    {
        return new EveryRules();
    }

    public static function datetime(DateTimeInterface $datetime): string
    {
        return $datetime->format('Y-m-d H:i:s');
    }

    public static function date(DateTimeInterface $datetime): string
    {
        return $datetime->format('Y-m-d');
    }
}
