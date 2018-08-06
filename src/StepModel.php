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

    /**
     * The step data as provided by the wizard and modified by the step.
     *
     * While this is public, always consider if it is appropriate for your step to modified the values
     * in here directly. Often it is the wizard which should be modifying the data using it's central
     * access to the wizard data.
     *
     * Also **do not** replace this array with another. e.g.
     *
     * $model->stepData = ["a" => "b"];
     *
     * The original array is actually a reference to an array passed from the wizard. If you replace the
     * overall array, data bindings will not be preserved between steps.
     *
     * @var array
     */
    public $stepData;

    public function __construct()
    {
        parent::__construct();

        $this->navigateToStepEvent = new Event();
    }

    public function setStepData(&$stepData)
    {
        $this->stepData = &$stepData;

        $this->bindingSource = &$stepData;
    }
}
