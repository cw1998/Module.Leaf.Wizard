# Changelog

### 1.0.2

Changed:    `onLeaving()` and `onLeft()` functions now take the `$nextStepName` as a parameter.

### 1.0.1

Added:      To allow for methods to be called before changing a step - `onLeaving()` and `onLeft()` methods to `Step`.
`beforeChangeStep()`, `afterChangeStep()`, `createSteps()` to Wizard. 

Changed:    `getSteps()` modified to return `$this->steps`, which it will set (using `createSteps()`)if it is null. 

### 1.0.0

Added:      Changelog
