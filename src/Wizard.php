<?php

namespace Rhubarb\Leaf\Wizard;

use Rhubarb\Crown\Request\Request;
use Rhubarb\Crown\Request\WebRequest;
use Rhubarb\Crown\UrlHandlers\UrlHandler;
use Rhubarb\Leaf\Leaves\Leaf;
use Rhubarb\Leaf\Leaves\LeafModel;
use Rhubarb\Leaf\Wizard\Exceptions\StepNotAvailableException;

abstract class Wizard extends Leaf
{
    /**
     * @var WizardModel
     */
    protected $model;

    protected abstract function getSteps(): array;

    /**
     * Returns the name of the standard view used for this leaf.
     *
     * @return string
     */
    protected function getViewClass()
    {
        return WizardView::class;
    }

    protected function onModelCreated()
    {
        parent::onModelCreated();

        $this->model->navigateToStepEvent->attachHandler(function($stepName){
            $this->changeStep($stepName);
        });

        // Get the first step name as our default starting step.
        $this->changeStep($this->getDefaultStep());
    }

    private function getDefaultStep()
    {
        $urlHandler = UrlHandler::getExecutingUrlHandler();
        $urlHandled = $urlHandler->getHandledUrl();

        /**
         * @var WebRequest $request
         */
        $request = Request::current();
        $url = $request->urlPath;

        $left = str_replace($urlHandled, '', $url);

        if (preg_match("/^([^\/]+)/", $left, $matches)){
            return $matches[1];
        }

        return key($this->getSteps());
    }

    private function changeStep($stepName)
    {
        $steps = $this->getSteps();

        if (!isset($steps[$stepName])){
            throw new StepNotAvailableException($stepName);
        }

        $this->model->currentStepName = $stepName;
    }

    /**
     * Should return a class that derives from LeafModel
     *
     * @return LeafModel
     */
    protected function createModel()
    {
        $model = new WizardModel();
        $model->steps = $this->getSteps();

        return $model;
    }
}