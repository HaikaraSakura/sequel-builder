<?php

use Haikara\SequelBuilder\Builder\Delete;
use Haikara\SequelBuilder\Builder\Insert;
use Haikara\SequelBuilder\Builder\Select;
use Haikara\SequelBuilder\Builder\SQL;
use Haikara\SequelBuilder\Builder\Update;
use Haikara\SequelBuilder\Raw;
use Haikara\SequelBuilder\Rules\Rules;

require_once __DIR__ . '/../vendor/autoload.php';

$now = new DateTimeImmutable;

$builder = (new Select)
    ->table('items')
    ->where(fn(Rules $rules) => $rules
        ->equals('item_name', '商品1')
        ->in('category_id', [1, 2])
    );
echo $builder . PHP_EOL;

echo $builder->buildFoundRows() . PHP_EOL;

echo (new Select)
    ->table('items')
    ->joinEquals('LEFT', 'item_categories', 'item_categories.id', new Raw('items.category_id'))
    ->where(fn(Rules $rules) => $rules
        ->equals('item_name', '商品1')
        ->in('category_id', [1, 2])
    );

echo PHP_EOL;

$builder = (new Select)
    ->table('items')
    ->joinEquals('LEFT', 'item_categories', 'item_categories.id', new Raw('items.category_id'))
    ->where(fn(Rules $rules) => $rules
        ->equals('item_name', '商品1')
        ->between('created_at', '2020-01-01 00:00:00', '2022-12-31 23:59:59')
    );
echo $builder;
print_r($builder);

echo PHP_EOL;

$builder = (new Insert)
    ->table('items')
    ->values([
        'item_name' => '商品2',
        'category_id' => 2
    ]);
echo $builder;
print_r($builder->getValues());

echo PHP_EOL;

$builder = (new Update)
    ->table('items')
    ->sets([
        'item_name' => '商品2',
        'category_id' => 2
    ])
    ->where(fn(Rules $rules) => $rules->equals('id', 2));
echo $builder;
print_r($builder->getValues());

echo PHP_EOL;

$builder = (new Delete)
    ->table('items')
    ->where(fn(Rules $rules) => $rules
        ->equals('id', 3)
    );
echo $builder;
print_r($builder->getValues());

echo PHP_EOL;

$builder = SQL::select('items')
    ->where(fn(Rules $rules) => $rules
        ->any(fn(Rules $rules) => $rules
            ->equals('category_id', 1)
            ->equals('category_id', 2)
        )
    );
echo $builder;
print_r($builder->getValues());

$builder = SQL::select('items')
    ->where(fn(Rules $rules) => $rules
        ->likeForward('created_at', SQL::date($now))
    );
echo $builder;
print_r($builder->getValues());