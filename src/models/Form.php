<?php

declare(strict_types=1);

namespace JCIT\models;

use yii\base\Model;

abstract class Form extends Model
{
    protected bool|null $runResult = null;

    public function getRunResult(): ?bool
    {
        return $this->runResult;
    }

    public function run(): bool
    {
        $this->runResult = $this->runInternal();
        return $this->runResult;
    }

    /**
     * The form model should implement a run function that can be used in the controller
     */
    abstract protected function runInternal(): bool;
}
