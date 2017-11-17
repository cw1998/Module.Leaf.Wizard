<?php

namespace Rhubarb\Leaf\Wizard;

use Rhubarb\Crown\Events\Event;
use Rhubarb\Leaf\Leaves\LeafModel;

class StepModel extends LeafModel
{
    /**
     * Raise to change the wizard's step to the one passed as the argument.
     *
     * @var Event
     */
    public $navigateToStepEvent;

    private $stepData;

    public function __construct()
    {
        parent::__construct();

        $this->navigateToStepEvent = new Event();
    }

    public function setStepData(&$stepData)
    {
        $this->stepData = &$stepData;

        $this->bindingSource = &$stepData;

        $this->bindingSource["a"] = "b";
    }
}