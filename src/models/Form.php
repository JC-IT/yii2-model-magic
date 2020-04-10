<?php

namespace JCIT\models;

use yii\base\Model;

/**
 * Class Form
 * @package JCIT\models
 */
abstract class Form extends Model
{
    const SCENARIO_SYSTEM_ADMIN = 'systemAdmin';

    /**
     * @var boolean|null
     */
    protected $runResult;

    /**
     * @return bool|null
     */
    public function getRunResult(): ?bool
    {
        return $this->runResult;
    }

    public function init()
    {
        parent::init();
        $this->scenario = $this->scenario === self::SCENARIO_DEFAULT && \Yii::$app->user->can('admin')
            ? self::SCENARIO_SYSTEM_ADMIN
            : $this->scenario;
    }

    /**
     * @return bool
     */
    public function run(): bool
    {
        $this->runResult = $this->runInternal();
        return $this->runResult;
    }

    /**
     * The form model should implement a run function that can be used in the controller
     *
     * @return bool
     */
    abstract protected function runInternal(): bool;

    /**
     * @return array
     */
    public function scenarios(): array
    {
        $result = parent::scenarios();

        $result[self::SCENARIO_SYSTEM_ADMIN] = $result[self::SCENARIO_DEFAULT];

        return $result;
    }
}