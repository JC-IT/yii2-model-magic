<?php

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
 *
 * Class FilteredActiveDataProvider
 */
class FilteredActiveDataProvider extends ActiveDataProvider
{
    /**
     * @var int
     */
    public $batchSize = 100;

    /**
     * @var  Closure
     */
    public $filter;

    /**
     * @var  Closure
     */
    public $totalCount;

    /**
     * @return iterable
     */
    public function each(): iterable
    {
        $query = clone $this->query;
        return $this->filter($query->each());
    }

    /**
     * @param iterable $iterable
     * @return iterable|Iterator
     */
    protected function filter(iterable $iterable)
    {
        return isset($this->filter) ? filter($this->filter, $iterable) : $iterable;
    }

    /**
     * @return array
     */
    protected function prepareModels()
    {
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

    /**
     * @return int
     */
    protected function prepareTotalCount(): int
    {
        $query = clone $this->query;

        if (is_null($this->filter)) {
            return $query->count('*', $this->db);
        }

        if (isset($this->totalCount)) {
            return ($this->totalCount)($query);
        }

        return \iter\count($this->filter($query->each($this->batchSize, $this->db)));
    }
}
