<?php

namespace Rhubarb\Leaf\Wizard\Tests;

use Rhubarb\Crown\Application;
use Rhubarb\Crown\PhpContext;
use Rhubarb\Crown\Request\Request;
use Rhubarb\Crown\Request\WebRequest;
use Rhubarb\Crown\Tests\Fixtures\TestCases\RhubarbTestCase;
use Rhubarb\Crown\UrlHandlers\CallableUrlHandler;
use Rhubarb\Crown\UrlHandlers\UrlHandler;
use Rhubarb\Leaf\Controls\Common\Text\TextBox;
use Rhubarb\Leaf\Views\View;
use Rhubarb\Leaf\Wizard\Exceptions\StepNavigationForbiddenException;
use Rhubarb\Leaf\Wizard\Exceptions\StepNotAvailableException;
use Rhubarb\Leaf\Wizard\Step;
use Rhubarb\Leaf\Wizard\StepModel;
use Rhubarb\Leaf\Wizard\Wizard;
use Rhubarb\Leaf\Wizard\WizardModel;

class WizardTest extends RhubarbTestCase
{
    public static function setUpBeforeClass()
    {
        include_once __DIR__.'/../../vendor/rhubarbphp/rhubarb/platform/boot-rhubarb.php';

        parent::setUpBeforeClass();
    }

    public function testWizardShowsFirstStep()
    {
        $wizard = new TestWizard([
            "step1" => new StepTest("step1")
        ]);

        $request = new WebRequest();
        Application::current()->setCurrentRequest($request);

        $response = $wizard->generateResponse($request);

        // Observe that step1 is the output
        $this->assertContains("step1", $response->getContent());
    }

    public function testWizardCanProgress()
    {
        $wizard = new TestWizard([
            "step1" => new StepTest("step1"),
            "step2" => new StepTest("step2")
        ]);

        /**
         * @var WizardModel $model
         */
        $model = $wizard->getModelForTesting();
        $model->navigateToStepEvent->raise("step2");

        $request = new WebRequest();
        Application::current()->setCurrentRequest($request);

        $response = $wizard->generateResponse($request);

        // Observe that step2 is the output
        $this->assertContains("step2", $response->getContent());

        $this->expectException(StepNotAvailableException::class);

        $model->navigateToStepEvent->raise("step3");
    }

    public function testWizardCanUseUrlForStartingStep()
    {
        $request = new WebRequest();
        $request->urlPath = "/step2/";

        Application::current()->setCurrentRequest($request);

        $handler = new CallableUrlHandler(function(){
            return new TestWizard([
                "step1" => new StepTest("step1"),
                "step2" => new StepTest("step2")
            ]);
        });

        $handler->setUrl("/");

        UrlHandler::setExecutingUrlHandler($handler);

        $response = $handler->generateResponse($request);

        // Observe that step2 is the output
        $this->assertContains("step2", $response->getContent());
    }

    public function testStepsBindToWizard()
    {
        $wizard = new TestWizard([
            "step1" => new StepTest("step1")
        ]);

        $request = new WebRequest();
        $request->postData['TestWizard_step1_Forename'] = 'john';

        Application::current()->setCurrentRequest($request);

        $wizard->generateResponse($request);

        /**
         * @var WizardModel $model
         */
        $model = $wizard->getModelForTesting();

        $this->assertEquals($model->wizardData["step1"]["Forename"], "john");

        $wizardData = $wizard->getProtectedWizardData();

        $this->assertEquals($wizardData["step1"]["Forename"], "john");
    }

    public function testStepsCanNavigate()
    {
        $wizard = new TestWizard([
            "step1" => $step1 = new StepTest("step1"),
            "step2" => new StepTest("step2")
        ]);

        /**
         * @var StepModel $stepModel
         */
        $stepModel = $step1->getModelForTesting();
        $stepModel->navigateToStepEvent->raise('step2');

        /**
         * @var WizardModel $model
         */
        $model = $wizard->getModelForTesting();

        $this->assertEquals("step2", $model->currentStepName);
    }

    public function testStepsCantNavigate()
    {
        $wizard = new TestWizard([
            "step1" => $step1 = new StepTest("step1"),
            "step2" => $step2 = new StepTest("step2")
        ], function($stepName){
            return $stepName != "step2";
        });

        /**
         * @var StepModel $model
         */
        $model = $wizard->getModelForTesting();

        try {
            $model->navigateToStepEvent->raise("step2");
            $this->fail("Shouldn't be allowed to navigate to step2");
        } catch (StepNavigationForbiddenException $er){

        }

        try {
            $model->navigateToStepEvent->raise("step1");
        } catch (StepNavigationForbiddenException $er){
            $this->fail("Should be allowed to navigate to step1");
        }
    }

    public function testWizardCanActOnStepLeavingAndLeft()
    {
        $wizard = new TestWizard([
            "step1" => $step1 = new StepTest("step1"),
            "step2" => $step2 = new StepTest("step2")
        ], function($stepName){
            return true;
        });

        /**
         * @var WizardModel $model
         */
        $model = $wizard->getModelForTesting();
        $model->navigateToStepEvent->raise("step2");

        $this->assertNotNull($wizard->didLeaveFrom);
        $this->assertEquals("step1", $wizard->didLeaveFrom);
        $this->assertEquals("step2", $wizard->didLeaveTo);

        $model->navigateToStepEvent->raise("step1");

        $this->assertEquals("step2", $wizard->didLeftFrom);
        $this->assertEquals("step1", $wizard->didLeftTo);
    }
}

class TestWizard extends Wizard
{
    /**
     * @var array
     */
    protected $steps;
    /**
     * @var null
     */
    private $canNavigateCallback;

    public $didLeaveFrom;
    public $didLeaveTo;

    public $didLeftFrom;
    public $didLeftTo;

    public function __construct(array $steps, $canNavigateCallback = null)
    {
        $this->steps = $steps;

        parent::__construct();

        $this->canNavigateCallback = $canNavigateCallback;
    }

    protected function onLeavingStep($fromStep, $toStep)
    {
        $this->didLeaveFrom = $fromStep;
        $this->didLeaveTo = $toStep;
    }

    protected function onLeftStep($fromStep, $toStep)
    {
        $this->didLeftFrom = $fromStep;
        $this->didLeftTo = $toStep;
    }

    public function canNavigateToStep($step)
    {
        if ($this->canNavigateCallback){
            $callback = $this->canNavigateCallback;

            return $callback($step);
        }

        return true;
    }

    public function getProtectedWizardData()
    {
        return $this->getWizardData();
    }

    protected function createSteps(): array
    {
        return $this->steps;
    }
}

class StepTest extends Step
{
    protected function getViewClass()
    {
        return StepView::class;
    }

    protected function createModel()
    {
        return new StepModel();
    }

    protected function getStepTitle()
    {
        return "";
    }
}

class StepView extends View
{
    protected function createSubLeaves()
    {
        $this->registerSubLeaf(
            new TextBox("Forename")
        );

        parent::createSubLeaves();
    }

    protected function printViewContent()
    {
        print $this->model->leafName;
        print $this->leaves["Forename"];
    }
}