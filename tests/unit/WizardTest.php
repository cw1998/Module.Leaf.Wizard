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
            'step1' => new StepTest(),
            'step2' => new StepTest()
        ]);

        $request = new WebRequest();
        Application::current()->setCurrentRequest($request);

        $response = $wizard->generateResponse($request);

        // Observe that step1 is the output
        $this->assertContains("step1", $response->getContent());
    }

    public function testWizardCurrentStepIsFirstStep()
    {
        $wizard = new TestWizard([
            'step1' => new StepTest(),
            'step2' => new StepTest()
        ]);

        /** @var WizardModel $model */
        $model = $wizard->getModelForTesting();

        $this->assertEquals('step1', $model->currentStepName);
    }

    public function testWizardCanProgress()
    {
        $wizard = new TestWizard([
            'step1' => new StepTest(),
            'step2' => new StepTest()
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
                'step1' => new StepTest(),
                'step2' => new StepTest()
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
            'step1' => new StepTest(),
            'step2' => new StepTest()
        ]);

        $request = new WebRequest();
        $request->postData['TestWizard_StepTest_Forename'] = 'john';

        Application::current()->setCurrentRequest($request);

        $wizard->generateResponse($request);

        /**
         * @var WizardModel $model
         */
        $model = $wizard->getModelForTesting();

        $this->assertEquals('john', $model->wizardData["step1"]["Forename"]);
    }

    public function testStepsCanNavigate()
    {
        $wizard = new TestWizard([
            'step1' => new StepTest(),
            'step2' => new StepTest()
        ]);

        $step1 = $wizard->getProtectedSteps()['step1'];

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
        $wizard = new TestWizardNoNav();

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

    public function testRequestingStepTwoDataGetsStepOneData()
    {
        $wizard = new TestWizardSharedData();

        /** @var WizardModel $wizardModel */
        $wizardModel = $wizard->getModelForTesting();

        $stepOneData = &$wizardModel->wizardData['step1'];
        $stepOneData['test'] = 'step 1 test data';

        $stepTwoData = $wizardModel->wizardData['step2'];

        $this->assertEquals('step 1 test data', $stepTwoData['test']);
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

        $model->navigateToStepEvent->raise("step1");

        $this->assertNull($wizard->didLeaveFrom);

        $model->navigateToStepEvent->raise("step2");

        $this->assertNotNull($wizard->didLeaveFrom);
        $this->assertEquals("step1", $wizard->didLeaveFrom);
        $this->assertEquals("step2", $wizard->didLeaveTo);

        $wizard->didLeftFrom = null;

        $model->navigateToStepEvent->raise("step2");

        $this->assertNull($wizard->didLeftFrom);

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

    public function getProtectedSteps(){
        return $this->steps;
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

    public function canNavigateToStep(string $step)
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

class TestWizardSharedData extends Wizard
{
    protected function createSteps(): array
    {
        return [
            'step1' => new StepTest(),
            'step2' => new StepSharedTest(),
            'step3' => new StepTest()
        ];
    }
    public function getProtectedSteps(){
        return $this->steps;
    }
}
class TestWizardNoNav extends Wizard
{
    protected function createSteps(): array
    {
        return [
            'step1' => new StepTest(),
            'step2' => new StepTest()
        ];
    }
    public function getProtectedSteps(){
        return $this->steps;
    }
    protected function canNavigateToStep(string $stepName): bool
    {
        return $stepName != 'step2';
    }
}
class StepTest extends Step
{
    protected function getViewClass()
    {
        return StepView::class;
    }
}
class StepSharedTest extends Step
{
    public function getStepDataBindingKey()
    {
        return 'step1';
    }
    protected function getViewClass()
    {
        return StepView::class;
    }
}

class StepView extends \Rhubarb\Leaf\Wizard\StepView
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
