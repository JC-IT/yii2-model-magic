<?php
declare(strict_types=1);

namespace JCIT\models\form;

use JCIT\models\Form;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

abstract class ActiveForm extends Form
{
    protected string $baseModelClass;
    protected bool $safeOnly = true;

    public function __construct(
        protected ActiveRecord $model,
        $config = [],
    ) {
        parent::__construct($config);
    }

    public function attributeHints(): array
    {
        return ArrayHelper::merge(
            parent::attributeHints(),
            array_intersect_key($this->model->attributeHints(), $this->getDataAttributes())
        );
    }

    public function attributeLabels(): array
    {
        return ArrayHelper::merge(
            parent::attributeLabels(),
            array_intersect_key($this->model->attributeLabels(), $this->getDataAttributes())
        );
    }

    public function init()
    {
        parent::init();

        if (!isset($this->baseModelClass) || !is_subclass_of($this->baseModelClass, ActiveRecord::class)) {
            throw new InvalidConfigException('BaseModelClass must be set and must be subclass of ' . ActiveRecord::class);
        }

        if (!is_subclass_of(get_class($this->model), $this->baseModelClass) && get_class($this->model) !== $this->baseModelClass) {
            throw new InvalidArgumentException('Model must be subclass of ' . $this->baseModelClass);
        }

        $this->initDataAttributes();
    }

    /**
     * Must return the data attributes from this form model that must be loaded into the active record model
     */
    protected function getDataAttributes(): array
    {
        return $this->attributes;
    }

    public function getModel(): ActiveRecord
    {
        return $this->model;
    }

    protected function initDataAttributes(): void
    {
        $values = array_intersect_key($this->model->attributes, $this->getDataAttributes());
        $this->setAttributes($values, false);
    }

    protected function runInternal(): bool
    {
        if ($result = $this->validate()) {
            $transaction = $this->model::getDb()->beginTransaction();
            $transactionLevel = $transaction->level;

            try {
                $result = $this->runInternalModel();

                if ($result) {
                    $transaction->commit();
                }
            } finally {
                if ($transaction->isActive && $transaction->level === $transactionLevel) {
                    $transaction->rollBack();
                }
            }
        }

        return $result;
    }

    protected function runInternalModel(): bool
    {
        $this->model->setAttributes($this->getDataAttributes(), $this->safeOnly);
        return $this->model->save();
    }
}
