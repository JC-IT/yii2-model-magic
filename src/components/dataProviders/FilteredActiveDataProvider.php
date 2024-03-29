<?php

declare(strict_types=1);

namespace JCIT\components\dataProviders;

use Closure;
use Iterator;
use yii\base\InvalidConfigException;
use yii\data\ActiveDataProvider;
use yii\db\Query;
use yii\db\QueryInterface;
use function iter\filter;
use function iter\slice;
use function iter\toArray;

/**
 * DataProvider that filters the records the user is allowed to see
 */
class FilteredActiveDataProvider extends ActiveDataProvider
{
    public int $batchSize = 100;
    public Closure $filter;
    public Closure $totalCount;

    public function each(): iterable
    {
        $query = clone $this->query;
        return $this->filter($query->each());
    }

    protected function filter(iterable $iterable): iterable
    {
        return isset($this->filter) ? filter($this->filter, $iterable) : $iterable;
    }

    protected function prepareModels(): array
    {
        if (!isset($this->filter)) {
            return parent::prepareModels();
        }

        if (!$this->query instanceof QueryInterface) {
            throw new InvalidConfigException('The "query" property must be an instance of a class that implements the QueryInterface e.g. yii\db\Query or its subclasses.');
        }

        /** @var QueryInterface|Query $query */
        $query = clone $this->query;
        if (($sort = $this->getSort()) !== false) {
            $query->addOrderBy($sort->getOrders());
        }

        if (($pagination = $this->getPagination()) !== false) {
            $pagination->totalCount = $this->getTotalCount();
            \Yii::beginProfile('pagination', self::class);
            $filtered = $this->filter($query->each($this->batchSize, $this->db));
            $result = toArray(slice($filtered, $pagination->getOffset(), $pagination->getLimit()));
            \Yii::endProfile('pagination', self::class);
            return $result;
        }

        return toArray($this->filter($query->each($this->batchSize, $this->db)));
    }

    protected function prepareTotalCount(): int
    {
        $query = clone $this->query;

        if (isset($this->totalCount)) {
            return ($this->totalCount)($query);
        }

        if (!isset($this->filter)) {
            return (int) $query->count('*', $this->db);
        }

        return \iter\count($this->filter($query->each($this->batchSize, $this->db)));
    }
}
