<?php

namespace Rhubarb\Leaf\Wizard;

use Rhubarb\Crown\Events\Event;
use Rhubarb\Leaf\Leaves\LeafModel;

class WizardModel extends LeafModel
{
    /**
     * @var Step[]
     */
    public $steps;

    /**
     * Raised to navigate to a new step.
     *
     * @var Event
     */
    public $navigateToStepEvent;

    /**
     * The current step
     *
     * @var string
     */
    public $currentStepName;

    /**
     * Central binding source for wizard data.
     *
     * @var array
     */
    public $wizardData = [];

    public function __construct()
    {
        parent::__construct();

        $this->navigateToStepEvent = new Event();
    }
}