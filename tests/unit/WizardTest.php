<?php

namespace Rhubarb\Leaf\Wizard;

use Rhubarb\Crown\Application;
use Rhubarb\Crown\Request\Request;
use Rhubarb\Crown\Request\WebRequest;
use Rhubarb\Crown\Tests\Fixtures\TestCases\RhubarbTestCase;
use Rhubarb\Crown\UrlHandlers\CallableUrlHandler;
use Rhubarb\Crown\UrlHandlers\ClassMappedUrlHandler;
use Rhubarb\Crown\UrlHandlers\UrlHandler;
use Rhubarb\Leaf\Controls\Common\Text\TextBox;
use Rhubarb\Leaf\Leaves\LeafModel;
use Rhubarb\Leaf\Views\View;
use Rhubarb\Leaf\Wizard\Exceptions\StepNotAvailableException;

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

        $response = $wizard->generateResponse(new WebRequest());

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

        $response = $wizard->generateResponse(new WebRequest());

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
}

class TestWizard extends Wizard
{
    /**
     * @var array
     */
    private $steps;

    public function __construct(array $steps)
    {
        $this->steps = $steps;

        parent::__construct();
    }

    public function getProtectedWizardData()
    {
        return $this->getWizardData();
    }

    protected function getSteps(): array
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