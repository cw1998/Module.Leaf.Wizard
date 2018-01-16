<?php

namespace Rhubarb\Leaf\Wizard\Exceptions;

use Rhubarb\Crown\Exceptions\RhubarbException;

class AbortChangeStepException extends RhubarbException
{
    public function __construct($stepName, \Exception $previous = null)
    {
        parent::__construct("Navigation to step `".$stepName."` aborted.", $previous);
    }
}