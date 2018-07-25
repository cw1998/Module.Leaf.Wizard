<?php

namespace Rhubarb\Leaf\Wizard\Tests;

use Codeception\Test\Unit;
use Codeception\Util\Stub;
use function foo\func;
use Rhubarb\Crown\Application;
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
        $wizard = new TestWizard();

        $response = $wizard->generateResponse(new WebRequest());

        // Observe that step1 is the output
        $this->assertContains("step1", $response->getContent());
    }

    public function testWizardCurrentStepIsFirstStep()
    {
        $wizard = new TestWizard();

        /** @var WizardModel $model */
        $model = $wizard->getModelForTesting();

        $this->assertEquals('step1', $model->currentStepName);
    }

    public function testWizardCanProgress()
    {
        $wizard = new TestWizard();

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
            return new TestWizard();
        });

        $handler->setUrl("/");

        UrlHandler::setExecutingUrlHandler($handler);

        $response = $handler->generateResponse($request);

        // Observe that step2 is the output
        $this->assertContains("step2", $response->getContent());
    }

    public function testStepsBindToWizard()
    {
        $wizard = new TestWizard();

        $request = new WebRequest();
        $request->postData['TestWizard_StepTest_Forename'] = 'john';

        $wizard->generateResponse($request);

        /**
         * @var WizardModel $model
         */
        $model = $wizard->getModelForTesting();

        $this->assertEquals('john', $model->wizardData["step1"]["Forename"]);
    }

    public function testStepsCanNavigate()
    {
        $wizard = new TestWizard();

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
}

class TestWizard extends Wizard
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