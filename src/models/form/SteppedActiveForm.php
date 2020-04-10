<?php

namespace JCIT\models\form;

use yii\helpers\ArrayHelper;
use yii\helpers\Json;

/**
 * Class SteppedActiveForm
 * @package JCIT\models\form
 */
abstract class SteppedActiveForm extends ActiveForm
{
    /**
     * @var int
     */
    public $currentStep = 0;

    /**
     * @var array
     */
    public $data = [];

    /**
     * @param string $scenario
     * @param int|null $step
     * @return string
     */
    public function calculateScenario(string $scenario, int $step = null): string
    {
        return $scenario . ($step ?? $this->currentStep);
    }

    /**
     * @return array
     */
    public function getCurrentStepAttributes(): array
    {
        return $this->steps()[$this->currentStep];
    }

    /**
     * @return string
     */
    public function getJsonData(): string
    {
        $data = $this->data;
        $data['currentStep'] = $this->currentStep;
        return \Yii::$app->security->hashData(Json::encode($data), \Yii::$app->request->cookieValidationKey);
    }

    /**
     * @param int|null $step
     * @return array
     */
    public function getStepAttributes(int $step = null): array
    {
        $steps = $this->steps();

        if (ArrayHelper::isIndexed($steps)) {
            $steps = [self::SCENARIO_DEFAULT => $steps];
        }

        return $steps[$this->scenario][$step ?? $this->currentStep];
    }

    /**
     * @param string $attribute
     * @return bool
     */
    public function isAttributeActiveInStep($attribute): bool
    {
        return in_array($attribute, $this->getStepAttributes());
    }

    /**
     * @param string $attribute
     * @return bool
     */
    public function isAttributeVisibleInStep($attribute): bool
    {
        $attributesSoFar = [];
        for ($i = 0; $i <= $this->currentStep; $i++) {
            $attributesSoFar = ArrayHelper::merge($attributesSoFar, $this->getStepAttributes($i));
        }
        return in_array($attribute, $attributesSoFar);
    }

    /**
     * @return bool
     */
    public function isLastStep(): bool
    {
        return $this->currentStep === count($this->steps());
    }

    /**
     * @param array $data
     * @param null $formName
     * @return bool
     */
    public function load($data, $formName = null): bool
    {
        if ($jsonData = ArrayHelper::getValue($data,  $this->formName() . '.jsonData')) {
            $this->setJsonData($jsonData);

            foreach ($this->data as $attribute => $value) {
                $this->{$attribute} = $value;
            }
        }

        $this->scenario .= $this->currentStep;
        $result = parent::load($data, $formName);
        $this->scenario = substr($this->scenario, 0, -1);

        return $result;
    }

    /**
     * @return bool
     */
    protected function runInternal(): bool
    {
        $this->scenario .= $this->currentStep;
        if ($this->isLastStep()) {
            $result = parent::runInternal();
        } else {
           if($result = $this->validate()) {
               $this->storeData();
               $this->currentStep++;
               $result = false;
           }
        }
        $this->scenario = substr($this->scenario, 0, -1);

        return $result;
    }

    /**
     * @return array
     */
    public function scenarios(): array
    {
        $result = [];
        $steps = $this->steps();
        if (ArrayHelper::isIndexed($steps, true)) {
            $steps = [self::SCENARIO_DEFAULT => $steps];
        }

        foreach ($steps as $scenario => $scenarioSteps) {
            foreach ($scenarioSteps as $step => $attributes) {
                $result[$this->calculateScenario($scenario, $step)] = $attributes;
            }
        }

        return $result;
    }

    /**
     * @param string $data
     */
    public function setJsonData(string $data): void
    {
        $validatedData = \Yii::$app->security->validateData($data, \Yii::$app->request->cookieValidationKey);
        $decodedData = Json::decode($validatedData);
        $this->currentStep = ArrayHelper::remove($decodedData, 'currentStep');
        $this->data = $decodedData;
    }

    /**
     * Must return an array of arrays containing the active attributes, just as if they were scenarios
     * example: [
     *     ['attribute1', 'attribute2'],
     *     ['attribute3', 'attribute4'],
     *     ['attribute5'],
     * ]
     *
     * You can specify keys and an extra level of arrays to support multiple scenarios
     * example: [
     *     self::SCENARIO_DEFAULT => [
     *         ['attribute1', 'attribute2'],
     *         ['attribute3', 'attribute4'],
     *         ['attribute5'],
     *     ],
     *     'admin' => [
     *         ['attribute1', 'attribute2', 'attribute6'],
     *         ['attribute3', 'attribute4'],
     *         ['attribute5', 'attribute7'],
     *     ]
     * ]
     *
     * @return array
     */
    abstract public function steps(): array;

    protected function storeData(): void
    {
        $data = $this->data ?? [];
        foreach ($this->attributes as $attribute => $value) {
            if ($this->isAttributeActive($attribute)) {
                $data[$attribute] = $this->{$attribute};
            }
        }

        $this->data = $data;
    }
}