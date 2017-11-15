<?php

namespace Rhubarb\Leaf\Wizard;

use Rhubarb\Leaf\Views\View;

class WizardView extends View
{
    /**
     * @var WizardModel
     */
    protected $model;

    protected function printViewContent()
    {
        print $this->model->steps[$this->model->currentStepName];
    }
}