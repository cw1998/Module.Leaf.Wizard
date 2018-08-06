<?php

use Rhubarb\Crown\Tests\Fixtures\TestCases\RhubarbTestCase;

class WizardStepTest extends RhubarbTestCase
{
    public function testConstructorCreatesNavigateToStepEvent()
    {
        $step = new Step();

        $this->assertNotNull($step->navigateToStepEvent);
    }

    public function testCreateModelCreatesStepModel()
    {
        $step = new Step();

        $stepModel = $step->getModelForTesting();

        $this->assertInstanceOf(\Rhubarb\Leaf\Wizard\StepModel::class, $stepModel);
    }

    public function testGetCustomStepDataKeyExists()
    {
        $this->assertTrue(method_exists(new Step(), 'getStepDataBindingKey'));
    }

    public function testGetCustomStepDataKeyReturnsNullIfCustomKeyNotSet()
    {
        $step = new Step();

        $this->assertNull($step->getStepDataBindingKey());
    }

}


class Step extends \Rhubarb\Leaf\Wizard\Step
{
    protected function getViewClass()
    {
        return \Rhubarb\Leaf\Wizard\StepView::class;
    }
}

class CustomBindStep extends \Rhubarb\Leaf\Wizard\Step
{
    public function getStepDataBindingKey()
    {
        return 'customStepDataKeyChange';
    }

    protected function getViewClass()
    {
        return \Rhubarb\Leaf\Wizard\StepView::class;
    }
}
