<?php

declare(strict_types=1);

namespace Haikara\SequelBuilder\Builder;

use Closure;
use Haikara\SequelBuilder\Raw;
use Haikara\SequelBuilder\Rules\EveryRules;
use Haikara\SequelBuilder\Rules\Rules;

use LogicException;

use function is_string;

abstract class Builder implements BuilderInterface
{
    protected string $table;
    protected ?string $alias = null;
    protected string $type;
    protected array $columns = [];
    protected array $join = [];
    protected ?EveryRules $where = null;
    protected array $group = [];
    protected array $order = [];
    protected ?EveryRules $having = null;
    protected ?string $limit = null;
    protected ?string $offset = null;
    protected array $values = [];

    const SELECT = 'SELECT';
    const INSERT = 'INSERT';
    const UPDATE = 'UPDATE';
    const DELETE = 'DELETE';

    const TYPES = [
        self::SELECT,
        self::INSERT,
        self::UPDATE,
        self::DELETE
    ];

    abstract protected function getType(): string;

    abstract protected function getQuery(): string;

    public function __toString(): string
    {
        return $this->getQuery();
    }

    public function __construct()
    {
        $this->type = $this->getType();
    }

    public function table(string $table, ?string $alias = null): static
    {
        $this->table = $table;
        $this->alias = $alias;

        return $this;
    }

    public function setAlias(string $alias): static
    {
        $this->alias = $alias;

        return $this;
    }

    public function when(bool $case, Closure $then, ?Closure $else = null): static
    {
        if ($case) {
            $then($this);
        } elseif (isset($else)) {
            $else($this);
        }

        return $this;
    }

    public function leftJoin(string $table, Closure|EveryRules $rules): static
    {
        return $this->join('LEFT', $table, $rules);
    }

    public function rightJoin(string $table, Closure|EveryRules $rules): static
    {
        return $this->join('RIGHT', $table, $rules);
    }

    public function outerJoin(string $table, Closure|EveryRules $rules): static
    {
        return $this->join('OUTER', $table, $rules);
    }

    public function leftJoinSubQuery(Select $sub_query, string $alias, Closure|EveryRules $rules): static
    {
        return $this->joinSubQuery('LEFT', $sub_query, $alias, $rules);
    }

    public function rightJoinSubQuery(Select $sub_query, string $alias, Closure|EveryRules $rules): static
    {
        return $this->joinSubQuery('RIGHT', $sub_query, $alias, $rules);
    }

    public function outerJoinSubQuery(Select $sub_query, string $alias, Closure|EveryRules $rules): static
    {
        return $this->joinSubQuery('OUTER', $sub_query, $alias, $rules);
    }

    protected function join(string $type, string $table, Closure|EveryRules $rules): static
    {
        if (!in_array($type, ['LEFT', 'RIGHT', 'OUTER'], true)) {
            throw new LogicException('$typeはLEFT、RIGHT、OUTERのいずれかを指定してください。');
        }

        if ($rules instanceof Closure) {
            $rules = $rules(new EveryRules);
        }

        $this->join[] = "$type JOIN $table ON $rules";

        $this->values['join'] = [
            ...$this->values['join'] ?? [],
            ...$rules->getValues()
        ];

        return $this;
    }

    protected function joinSubQuery(string $type, Select $sub_query, string $alias, Closure|EveryRules $rules): static
    {
        if (!in_array($type, ['LEFT', 'RIGHT', 'OUTER'], true)) {
            throw new LogicException('$typeはLEFT、RIGHT、OUTERのいずれかを指定してください。');
        }

        if ($rules instanceof Closure) {
            $rules = $rules(new EveryRules);
        }

        $this->join[] = "$type JOIN ($sub_query) AS $alias ON $rules";

        $this->values['join'] = [
            ...$this->values['join'] ?? [],
            ...$sub_query->getValues(),
            ...$rules->getValues()
        ];

        return $this;
    }

    public function leftJoinEquals(string $table, string $column1, string|Raw $column2): static
    {
        return $this->joinEquals('LEFT', $table, $column1, $column2);
    }

    public function rightJoinEquals(string $table, string $column1, string|Raw $column2): static
    {
        return $this->joinEquals('RIGHT', $table, $column1, $column2);
    }

    public function outerJoinEquals(string $table, string $column1, string|Raw $column2): static
    {
        return $this->joinEquals('OUTER', $table, $column1, $column2);
    }

    public function joinEquals(string $type, string $table, string $column1, string|Raw $column2): static
    {
        if (!in_array($type, ['LEFT', 'RIGHT', 'OUTER'], true)) {
            throw new LogicException('$typeはLEFT、RIGHT、OUTERのいずれかを指定してください。');
        }

        if (is_string($column2)) {
            $this->values['join'][] = $column2;
        }

        if ($column2 instanceof Raw) {
            $this->values['join'] = [
                ...$this->values['join'] ?? [],
                ...$column2->getValues()
            ];
        }

        $this->join[] = "$type JOIN $table ON $column1 = $column2";

        return $this;
    }

    public function joinRaw(string|Raw $statement): static
    {
        $this->join[] = $statement;

        if ($statement instanceof Raw) {
            $this->values['join'] = [
                ...$this->values['join'] ?? [],
                ...$statement->getValues()
            ];
        }

        return $this;
    }

    public function where(Closure|EveryRules $rules): static
    {
        if ($this->issetWhere()) {
            throw new LogicException('whereプロパティにRulesオブジェクトが格納済みです。上書きはできません。');
        }

        $this->where = ($rules instanceof EveryRules) ? $rules : new EveryRules;

        if ($rules instanceof Closure) {
            $rules($this->where);
        }

        $this->values['where'] = $this->where->getValues();

        return $this;
    }

    public function getWhere(): string
    {
        return $this->where->hasRule() ? "WHERE $this->where" : '';
    }

    public function getHaving(): string
    {
        return $this->having->hasRule() ? "HAVING $this->having" : '';
    }

    public function groupBy(string $column): static
    {
        $this->group[] = $column;
        return $this;
    }

    public function having(Closure|EveryRules $rule): static
    {
        if ($this->issetHaving()) {
            throw new LogicException('havingプロパティにEveryRulesオブジェクトが格納済みです。上書きはできません。');
        }

        $this->having = ($rule instanceof EveryRules) ? $rule : new EveryRules;
        if ($rule instanceof Closure) {
            $rule($this->having);
        }
        $this->values['having'] = $this->having->getValues();
        return $this;
    }

    public function orderBy(string $column, string $order): static
    {
        $order = strtoupper($order);

        if (!in_array($order, ['ASC', 'DESC'], true)) {
            throw new LogicException('$orderにはASCかDESCを指定してください。');
        }
        $this->order[] = "$column $order";
        return $this;
    }

    public function orderByAsc(string $column): static
    {
        $this->order[] = "$column ASC";
        return $this;
    }

    public function orderByDesc(string $column): static
    {
        $this->order[] = "$column DESC";
        return $this;
    }

    /**
     * @param int $limit_offset limitのみならこちらがlimit。limitとoffsetを渡す場合、こちらがoffset。
     * @param int|null $limit limitとoffsetを渡す場合、こちらがlimit
     * @return $this
     */
    public function limit(int $limit_offset, ?int $limit = null): static
    {
        $this->limit = '?';

        if (isset($limit)) {
            $this->offset = '?';
            $this->values['limit'] = $limit;
            $this->values['offset'] = $limit_offset;
        } else {
            $this->values['limit'] = $limit_offset;
            $this->values['offset'] = $limit;
        }

        return $this;
    }

    public function offset(int $offset): static
    {
        if (isset($this->values['offset'])) {
            throw new LogicException('OFFSETはセット済みです。');
        }

        $this->offset = '?';
        $this->values['offset'] = $offset;

        return $this;
    }

    public function columns(string|Raw ...$columns): static
    {
        foreach ($columns as $column) {
            $this->columns[] = $column;

            if ($column instanceof Raw) {
                $this->values['columns'] = [...($this->values['columns'] ?? []), ...$column->getValues()];
            }
        }

        return $this;
    }

    public function issetWhere(): bool
    {
        return $this->where instanceof Rules;
    }

    public function issetHaving(): bool
    {
        return $this->having instanceof Rules;
    }
}