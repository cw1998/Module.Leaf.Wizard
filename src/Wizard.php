<?php

namespace Rhubarb\Leaf\Wizard;

use Rhubarb\Crown\Request\Request;
use Rhubarb\Crown\Request\WebRequest;
use Rhubarb\Crown\UrlHandlers\UrlHandler;
use Rhubarb\Leaf\Leaves\Leaf;
use Rhubarb\Leaf\Leaves\LeafModel;
use Rhubarb\Leaf\Wizard\Exceptions\StepNavigationForbiddenException;
use Rhubarb\Leaf\Wizard\Exceptions\StepNotAvailableException;

/**
 * An abstract pattern for building 'wizards', i.e. step based journeys.
 */
abstract class Wizard extends Leaf
{
    /**
     * @var WizardModel
     */
    protected $model;

    /**
     * Returns an array of step name strings to Step object pairs which define the list of
     * possible steps.
     *
     * e.g.
     *
     * [
     *      self::STEP_PERSONAL => new PersonalDetailsStep(),
     *      self::STEP_ADDRESS => new AddressDetailsStep(),
     *      self::STEP_PAYMENT => new PaymentDetailsStep(),
     *      self::STEP_CONFIRM => new ConfirmDetailsStep()
     * ]
     *
     * As the step names are important for navigation it's best practice to use constants for these.
     *
     * @return array
     */
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

    /**
     * Called to determine if the step being navigated to is permitted in the current context.
     *
     * It is essential this method is implemented as a fail safe to stop people arriving at
     * steps which make no sense unless other steps are completed first.
     *
     * This is not just a courtesy for users, it's an important security consideration; a
     * malicious attacker could easily change the model state to 'skip' steps.
     *
     * @param $stepName string The name of the step being navigated to.
     * @return bool
     */
    protected function canNavigateToStep(string $stepName): bool
    {
        return true;
    }

    /**
     * Called to initialise wizard data from some persistent data source.
     *
     * For example you might need to pre populate the first step of a checkout
     * wizard with the customer's personal details. Or perhaps your wizard is
     * designed to let users edit existing data.
     *
     * This method should fetch the data and apply it to
     * $this->model->wizardData[stepname] as appropriate.
     */
    protected function loadDataFromPersistentState(): void
    {
    }

    protected function onModelCreated()
    {
        parent::onModelCreated();

        $this->model->navigateToStepEvent->attachHandler(function($stepName){
            $this->changeStep($stepName);
        });

        $this->loadDataFromPersistentState();

        // Get the first step name as our default starting step.
        $this->changeStep($this->getDefaultStep());
    }

    /**
     * Returns the name of the step to show by default.
     *
     * This will normally return the first step from the list of steps returned by
     * getSteps(), however if the URL contains an extra fragment that matches a
     * step name e.g.
     *
     * /path/to/wizard/directstepname
     *
     * If directstepname was a step this would be returned as the default step.
     *
     * This function can be overridden if the default step should be locked to a particular step
     * or if it should be derived from some data context.
     *
     * @return string
     */
    protected function getDefaultStep(): string
    {
        $urlHandler = UrlHandler::getExecutingUrlHandler();

        if ($urlHandler != null) {

            $urlHandled = $urlHandler->getHandledUrl();

            /**
             * @var WebRequest $request
             */
            $request = Request::current();

            if ($request instanceof WebRequest) {
                $url = $request->urlPath;

                $left = str_replace($urlHandled, '', $url);

                if (preg_match("/^([^\/]+)/", $left, $matches)) {
                    return $matches[1];
                }
            }
        }

        return key($this->getSteps());
    }

    /**
     * Called internally to try and change the step
     *
     * @param $stepName
     * @throws StepNavigationForbiddenException
     * @throws StepNotAvailableException
     */
    private function changeStep($stepName)
    {
        $steps = $this->getSteps();

        if (!isset($steps[$stepName])){
            throw new StepNotAvailableException($stepName);
        }

        if (!$this->canNavigateToStep($stepName)){
            throw new StepNavigationForbiddenException($stepName);
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

        foreach($model->steps as $stepName => $step){
            if (!isset($model->wizardData[$stepName])){
                $model->wizardData[$stepName] = [];
            }

            $step->setStepData($model->wizardData[$stepName]);
        }

        return $model;
    }

    protected function &getWizardData()
    {
        return $this->model->wizardData;
    }
}