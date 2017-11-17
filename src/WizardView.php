<?php

namespace Rhubarb\Leaf\Wizard;

use Rhubarb\Leaf\Views\View;

class WizardView extends View
{
    /**
     * @var WizardModel
     */
    protected $model;

    protected function onStateRestored()
    {
        parent::onStateRestored();

        foreach($this->model->steps as $stepName => $step){
            if (!isset($this->model->wizardData[$stepName])){
                $this->model->wizardData[$stepName] = [];
            }
            $data = &$this->model->wizardData[$stepName];
            $step->setStepData($data);
        }
    }

    protected function createSubLeaves()
    {
        foreach($this->model->steps as $step){
            $step->navigateToStepEvent->attachHandler(function($nextStep){
                $this->model->navigateToStepEvent->raise($nextStep);
            });

            $this->registerSubLeaf($step);
        }

        parent::createSubLeaves();
    }

    protected function printViewContent()
    {
        print $this->model->steps[$this->model->currentStepName];
    }
}