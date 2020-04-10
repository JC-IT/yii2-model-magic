<?php

namespace JCIT\models\search;

use Closure;
use JCIT\models\Search;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\data\DataProviderInterface;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;

/**
 * Class ActiveSearch
 * @package JCIT\models\search
 */
abstract class ActiveSearch extends Search
{
    /**
     * @var string
     */
    protected $baseModelClass;

    /**
     * @var Closure|null
     */
    protected $filter = null;

    /**
     * @var array
     */
    protected $pagination = [
        'pageSize' => 10,
    ];

    /**
     * @var ActiveQuery
     */
    protected $query;

    /**
     * @var array
     */
    protected $sort = [];

    /**
     * ActiveSearch constructor.
     * @param ActiveQuery $query
     * @param array $config
     */
    public function __construct(ActiveQuery $query, $config = [])
    {
        $this->query = $query;
        parent::__construct($config);
    }

    /**
     * @return array
     */
    public function attributeLabels(): array
    {
        $modelClass = $this->query->modelClass;
        return ArrayHelper::merge(
            parent::attributeLabels(),
            (new $modelClass())->attributeLabels()
        );
    }

    public function init()
    {
        parent::init();

        $this->performChecks();
    }

    /**
     * @return DataProviderInterface
     */
    protected function getBaseDataProvider(): DataProviderInterface
    {
        return \Yii::createObject(FilteredActiveDataProvider::class, [[
            'query' => $this->query,
            'filter' => $this->filter,
            'sort' => $this->sort,
            'pagination' => $this->pagination,
        ]]);
    }

    /**
     * @param DataProviderInterface $dataProvider
     * @return DataProviderInterface
     */
    protected function internalSearch(DataProviderInterface $dataProvider): DataProviderInterface
    {
        $query = $dataProvider->query;
        $this->internalSearchQuery($query);
        return $dataProvider;
    }

    /**
     * @param ActiveQuery $query
     */
    abstract protected function internalSearchQuery(ActiveQuery $query): void;

    /**
     * @throws InvalidConfigException
     */
    protected function performChecks(): void
    {
        if (!isset($this->baseModelClass) || !is_subclass_of($this->baseModelClass, ActiveRecord::class)) {
            throw new InvalidConfigException('BaseModelClass must be set and must be subclass of ' . ActiveRecord::class);
        }

        if (!is_subclass_of($this->query->modelClass, $this->baseModelClass) && $this->query->modelClass !== $this->baseModelClass) {
            throw new InvalidArgumentException('ModelClass of query must be subclass of ' . $this->baseModelClass);
        }
    }
}