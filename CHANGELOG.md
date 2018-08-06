# Changelog

### 1.1.0

Added:      Method to share data completely between one ore more steps (see documentation)

### 1.0.6

Changed:    Lifecycle methods only called now if the step is actually changing to a different step.

### 1.0.5

Added:      New hooks onLeavingStep and onLeftStep in the Wizard
Changed:    $stepData on StepModel is now public so steps can modify the step data (with caution!)      

### 1.0.4

Added:      Documentation!

### 1.0.3

Changed:    Removed void return type from `loadDataFromPersistentState()` to support PHP < 7.1

### 1.0.2

Changed:    `onLeaving()` and `onLeft()` functions now take the `$nextStepName` as a parameter.

### 1.0.1

Added:      To allow for methods to be called before changing a step - `onLeaving()` and `onLeft()` methods to `Step`.
`beforeChangeStep()`, `afterChangeStep()`, `createSteps()` to Wizard. 

Changed:    `getSteps()` modified to return `$this->steps`, which it will set (using `createSteps()`)if it is null. 

### 1.0.0

Added:      Changelog
