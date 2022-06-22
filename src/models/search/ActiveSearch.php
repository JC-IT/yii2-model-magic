<?php
declare(strict_types=1);

namespace JCIT\models\search;

use Closure;
use JCIT\components\dataProviders\FilteredActiveDataProvider;
use JCIT\models\Search;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\data\DataProviderInterface;
use yii\data\Pagination;
use yii\data\Sort;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

abstract class ActiveSearch extends Search
{
    protected string $baseModelClass;
    protected Closure|null $filter = null;
    protected Pagination|array $pagination = [
        'pageSize' => 10,
    ];
    protected Sort|array $sort = [];

    public function __construct(
        protected ActiveQuery $query,
        $config = []
    ) {
        parent::__construct($config);
    }

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

        if (!isset($this->baseModelClass) || !is_subclass_of($this->baseModelClass, ActiveRecord::class)) {
            throw new InvalidConfigException('BaseModelClass must be set and must be subclass of ' . ActiveRecord::class);
        }

        if (!is_subclass_of($this->query->modelClass, $this->baseModelClass) && $this->query->modelClass !== $this->baseModelClass) {
            throw new InvalidArgumentException('ModelClass of query must be subclass of ' . $this->baseModelClass);
        }
    }

    protected function getBaseDataProvider(): DataProviderInterface
    {
        return \Yii::createObject(FilteredActiveDataProvider::class, [array_filter([
            'query' => $this->query,
            'filter' => $this->filter,
            'sort' => $this->sort,
            'pagination' => $this->pagination,
        ])]);
    }

    protected function internalSearch(DataProviderInterface $dataProvider): DataProviderInterface
    {
        $query = $dataProvider->query;
        $this->internalSearchQuery($query);
        return $dataProvider;
    }

    abstract protected function internalSearchQuery(ActiveQuery $query): void;

}
