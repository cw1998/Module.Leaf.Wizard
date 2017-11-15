<?php

namespace Rhubarb\Leaf\Wizard\Exceptions;

use Rhubarb\Crown\Exceptions\RhubarbException;

class StepNotAvailableException extends RhubarbException
{
    public function __construct($stepName, \Exception $previous = null)
    {
        parent::__construct("The step name `".$stepName."` was not valid.", $previous);
    }

}