<?php

namespace Rhubarb\Leaf\Wizard;

use Rhubarb\Leaf\Views\View;

class StepView extends View
{
    protected function printViewContent()
    {
        parent::printViewContent();

        $this->printTop();
        $this->printBody();
        $this->printTail();
    }

    protected function printTop()
    {

    }

    protected function printBody()
    {

    }

    protected function printTail()
    {

    }
}