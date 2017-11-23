<?php

namespace Rhubarb\Leaf\Wizard;

use Rhubarb\Leaf\Controls\Common\Buttons\Button;
use Rhubarb\Leaf\Leaves\LeafDeploymentPackage;
use Rhubarb\Leaf\Views\View;

class StepView extends View
{
    /**
     * @var StepModel
     */
    protected $model;

    protected function createSubLeaves()
    {
        parent::createSubLeaves();

        $this->registerSubLeaf(
            new Button("navigate", "Next", function($step){
                $this->model->navigateToStepEvent->raise($step);
            }));
    }

    protected function printNavigationSubmitLink(string $text, string $stepName, string $cssClasses = "")
    {
        ?><a href="#" class="js-wizard-step-link <?=htmlentities($cssClasses);?>" data-step="<?=htmlentities($stepName);?>"><?=$text;?></a><?php
    }

    protected function printNavigateButton($text, $step)
    {
        /**
         * @var $button Button
         */
        $button = $this->leaves["navigate"];
        $button->setButtonText($text);
        $button->printWithIndex($step);
    }

    protected function printViewContent()
    {
        parent::printViewContent();

        $this->printTop();
        $this->printStepBody();
        $this->printTail();
    }

    protected function printTop()
    {

    }

    protected function printStepBody()
    {

    }

    protected function printTail()
    {

    }

    public function getDeploymentPackage()
    {
        return new LeafDeploymentPackage(__DIR__.'/StepViewBridge.js');
    }

    protected function getViewBridgeName()
    {
        return "StepViewBridge";
    }


}