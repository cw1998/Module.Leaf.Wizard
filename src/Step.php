<?php

namespace Rhubarb\Leaf\Wizard;

use Rhubarb\Crown\Events\Event;
use Rhubarb\Crown\Exceptions\RhubarbException;
use Rhubarb\Leaf\Exceptions\InvalidLeafModelException;
use Rhubarb\Leaf\Leaves\Leaf;

abstract class Step extends Leaf
{
    /**
     * @var $model StepModel
     */
    protected $model;

    public $navigateToStepEvent;

    public function __construct($name = null, $initialiseModelBeforeView = null)
    {
        $this->navigateToStepEvent = new Event();

        parent::__construct($name, $initialiseModelBeforeView);
    }

    protected function createModel()
    {
        return new StepModel();
    }

    protected function onModelCreated()
    {
        parent::onModelCreated();

        $this->model->navigateToStepEvent->attachHandler(function($newStepName){
            $this->navigateToStepEvent->raise($newStepName);
        });
    }

    public function setStepData(&$stepData)
    {
        if (!$this->model instanceof StepModel){
            throw new InvalidLeafModelException("Step leaves must use a StepModel");
        }

        $this->model->setStepData($stepData);
    }
}