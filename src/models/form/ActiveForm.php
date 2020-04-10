<?php

namespace JCIT\models\form;

use JCIT\models\Form;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * Class ActiveForm
 * @package common\models\form
 */
abstract class ActiveForm extends Form
{
    /**
     * @var string
     */
    protected $baseModelClass;

    /**
     * @var ActiveRecord
     */
    protected $model;

    /**
     * ActiveForm constructor.
     * @param ActiveRecord $model
     * @param array $config
     */
    public function __construct(ActiveRecord $model, $config = [])
    {
        $this->model = $model;
        parent::__construct($config);
    }

    /**
     * @return array
     */
    public function attributeHints(): array
    {
        return ArrayHelper::merge(
            parent::attributeHints(),
            array_intersect_key($this->model->attributeHints(), $this->getDataAttributes())
        );
    }

    /**
     * @return array
     */
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
     *
     * @return array
     */
    protected function getDataAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @return ActiveRecord
     */
    public function getModel(): ActiveRecord
    {
        return $this->model;
    }

    protected function initDataAttributes(): void
    {
        $values = array_intersect_key($this->model->attributes, $this->getDataAttributes());
        $this->setAttributes($values, false);
    }

    /**
     * @return bool
     */
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

    /**
     * @return bool
     */
    protected function runInternalModel(): bool
    {
        $this->model->setAttributes($this->getDataAttributes());
        return $this->model->save();
    }
}