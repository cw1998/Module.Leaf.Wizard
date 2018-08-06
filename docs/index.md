Wizards
=======

A multi-stepped data gathering process is often called a wizard. When people here the term 'wizard'
many naturally think of dialogs in traditional GUI software with next, back and cancel buttons. In
fact many web based systems also fit the description of a wizard even if the term 'wizard' would
not occur to the users. For example a checkout of an Ecommerce site generally has a details step, a
shipping step, a billing step a final review step and an acknowledgement step. Similarly onboarding
forms for new accounts are often stepped to guide the user through the journey and perhaps to
conceal the full size of the form from the user.

The wizard leaf module provides a pattern of classes to enable you to quickly build multi-step
systems and provides solutions to common issues such as moving between steps, retaining captured data,
and validating which steps are permissible.

> It can be helpful to remember that ultimately this pattern boils down to a parent 'host' leaf
> that contains multiple 'sub' leaves (the steps), but only one of which it shows at one time.

## Creating the wizard steps

For each step you need to make:

* a new Leaf that extends `Step`
* a new View that extends `StepView`
* a new Model that extends `StepModel`

### The StepView

A StepView is the canvas on which your present your steps UI and it behaves
just like a normal Leaf. You should create your sub leaves in the normal way
by extending `createSubLeaves`.

However most 'steps' in a wizard have a familiar surround and so while you
could extend the `printViewContent` function as normal, a StepView
has a pattern of three methods you can override instead:

`printTop()`
:   Common area above the content

`printTail()`
:   Common area below the content

`printStepBody()`
:   The step content itself

Its expected that you might make a base View for your wizard that implements
`printTop` or `printTail` in order to supply common elements like a step
indicator, and leave the `printStepBody` to be overriden by the 
actual step views.

The StepView also provides two important helper methods:

`printNavigationButton($text, $step)`
:   Prints a button that attempts to navigate to the step named by `$step` with
    `$text` as the button text.

`printNavigationSubmitLink($text, $step)`
:   As above but as a standard &lt;a&gt; tag.

Whether or not the navigation succeeds depends upon how the steps have been
wired together.

## The Wizard

To bring your steps together you must create your Wizard Leaf by extending
the `Wizard` class. `Wizard` is abstract and has one method that must
be defined:

```php
class Checkout extends Wizard
{
    protected function createSteps(): array
    {
        return [
            'details' => new PersonalDetailsStep(),
            'address' => new AddressStep(),
            'payment' => new PaymentStep().
            'confirm' => new ConfirmationStep()
        ];
    }
}
```

Note that there is no particular rhyme or reason in the keys used in the
array, however they do become important for navigation. Think of these
keys as aliases for referring to the steps later. As such its best practice
to define the keys as constants instead so that navigation logic can
be sure to get the right alias names:

```php
class Checkout extends Wizard
{
    const STEP_PERSONAL = 'personal';
    const STEP_ADDRESS = 'address';
    const STEP_PAYMENT = 'payment';
    const STEP_CONFIRM = 'confirm';
    
    protected function createSteps(): array
    {
        return [
            self::STEP_PERSONAL => new PersonalDetailsStep(),
            self::STEP_ADDRESS => new AddressStep(),
            self::STEP_PAYMENT => new PaymentStep().
            self::STEP_CONFIRM => new ConfirmationStep()
        ];
    }
}
```

You can of course create steps conditionally by reviewing state however
it is strongly discouraged. Ideally this function should return the same
array under any circumstances. The actual journey your user takes may
not touch all of these steps depending on how the navigation buttons
bring them through the wizard. That is fine - there is no problem having
the additional steps created. Also if you need to control the 'entry' or
default step you can do this in more considered way that changing the
order of the steps in this array.

### Controlling the entry step

By default the first step in the array returned by `createSteps` becomes
the default step.

To change this either because that is not the case, or because after
analysing the state a different step would be best simply override the 
`getDefaultStep()` method and return the alias name of the step
to use as the entry step.

Bear in mind the original implementation of this function first looks to
see if a step alias name is supplied in the Url and uses that in preference
to the first step. If you want to keep this behaviour don't forget to
call the parent implementation.

### Validating navigation permissions

While individual `StepView` objects should try to only show navigation links
to steps that make sense given the current state it is important that
the Wizard is stopped from navigating to invalid steps if either

1) A bug in StepView shows a navigation button or link to a step that should
not be allowed or
2) The user has appended the step name to the URL

To validate a step change you need to override the `canNavigateToStep`
method:

```php
class Checkout extends Wizard
{
    protected function canNavigateToStep(string $stepName): bool
    {
        switch($stepName){
            case: self::STEP_ADDRESS:
                if ($this->wizardData[self::STEP_PERSONAL]['FirstName'] == ''){
                    // Only once the user has entered a name should we be allowed
                    // to navigate to the address step.
                    return false;
                }
            break;
        }
        return true;
    }
}
```

While it's most common to see a switch statement here don't forget you can
apply more broad checks as well:

```php
class Checkout extends Wizard
{
    protected function canNavigateToStep(string $stepName): bool
    {
        if ($stepName != self::STEP_PERSONAL &&
            $this->wizardData[self::STEP_PERSONAL]['FirstName'] == '' ){
            
            // If the user hasn't entered a name then only the personal
            // details step is permissable.
                               
            return false;
        }
        
        return true;
    }
}
```

It's also common to expect some journeys in one direction only in which
case you can look at the `$this->model->currentStep` field:

```php
class Checkout extends Wizard
{
    protected function canNavigateToStep(string $stepName): bool
    {
        switch($stepName){
            case self::STEP_PAYMENT:
                if ($this->model->currentStep != self::STEP_CONFIRM &&
                $this->model->currentStep != self::STEP_ADDRESS){
                    // Only allow access to the payment step from either the
                    // address step (user moving forwards from left to right)
                    // or the confirm step (user changing their mind)
                    return false;
                }
                    
            break;
        }
        
        return true;
    }
}
```

A common requirement is to stop navigation if the current step is incomplete
or has a processing error. While this could be handled in `canNavigateToStep`
it would become a very unwieldy function. Ideally `canNavigateToStep` would
really describe the mechanics of how users are expected to negotiate through
the steps of your wizard.

When a navigation event is raised the wizard will call the `onLeaving`
method on the current step's Step class. If this function throws an
AbortChangeStepException then the navigation will fail.

### Wizard Lifecycle methods

There are two main lifecycle methods you may find useful. In each case
simply extend the appropriate method to implement:

`onLeavingStep($fromStep, $toStep)`
:   Called when the user is trying to navigate away from the current step.
    If you throw an AbortChangeStepException then the navigation will
    be cancelled.

`onLeftStep[]($fromStep, $toStep)`
:   Called after the step change has completed.

> Note that these lifecycle events are only fired if the user is navigating
> to a step other than the one they're currently on.

### Step Lifecycle methods

Similarly the wizard lifecycle methods are repeated on the steps themselves:

`onLeaving($targetStepName)`
:   Called when the step is the current step but the user is trying to navigate
    away. If you throw an AbortChangeStepException then the navigation will
    be cancelled.

`onLeft($targetStepName)`
:   Called after the step change has completed.

> Note that these lifecycle events are only fired if the user is navigating
> to a step other than the one they're currently on.

## Data Binding

The Wizard class creates a central array to gather all entered data from
all of the steps. It's important that your Step classes use a model class
that derives from `StepModel` as it understands how to interact with this
array and will bind the data from your step's controls to the central
array store.

> Data gathered by steps is retained across post backs by propagating the
> data in a hidden input on the page. You should be careful not to put
> into the wizard data array anything that should be kept private. You can
> define custom model properties outside of wizard data as normal for that
> purpose.

## Processing Data

When you need to process user events you can do this either in the step or
the wizard. Event processing in the step must confined to actions that can
be completed with only the data on that step. If your processing action
requires data gathered on other steps you must bubble the event up to
your wizard leaf class.

Simply define an event in your step class and then attach a handler in the
`createSteps()` method:

```php
class Checkout extends Wizard
{
    const STEP_PERSONAL = 'personal';
    const STEP_ADDRESS = 'address';
    const STEP_PAYMENT = 'payment';
    const STEP_CONFIRM = 'confirm';
    
    protected function createSteps(): array
    {
        $confirmStep = new ConfirmationStep();
        $confirmStep->placeOrderEvent->attachHandler(function(){
            // Do something to place the order...
        });
        
        return [
            self::STEP_PERSONAL => new PersonalDetailsStep(),
            self::STEP_ADDRESS => new AddressStep(),
            self::STEP_PAYMENT => new PaymentStep().
            self::STEP_CONFIRM => $confirmStep
        ];
    }
}
```

### Processing data in response to navigation

Sometimes you won't be defining your own buttons for the step, you want to
rely instead upon actions being committed when the user performs the
navigation to another step.

The easiest mechanism for this is to override either the `onLeft` method of
the Step class, or the `onLeftStep` method of the `Wizard` (depending on
if the saving action needs know about more than the single step or not).

## Advanced Techniques

### Persisting state

Some stepped systems are populating business models that could be stored
'as you go'. Others may want to initialise the wizard with data from the
business models, for example to load up the logged in customers details.

There are three main patterns for state persistence:

1. When capturing new data generally you let the wizard gather the data
from the steps and on the final step a button raises an event and your
wizard invokes the state storage.

2. For wizards that edit or amend existing data you can override
`loadDataFromPersistantState()` and return an array of wizard data, keyed
by the step alias name with values that are associate arrays of the 
initialised data.

3. In some cases you may abandon the central storage of wizard data provided
by the Wizard class and intentionally make your Step classes use a Leaf model
that *does not* extend from StepModel. You can then populate your own model
data and commit it for storage just as you would if this was a normal page
leaf. This means the wizard is just providing the mechanics around step
switching and validation but it can be appropriate. Perhaps your steps
are all self contained and by design should update the data store as the user
leaves each step.

### Step reuse

Sometimes you need to allow the user to arrive at the same step from
different places, however the user interface needs to change subtly
based on where the user came from. Most often the main change is that
where the 'next' button takes them would be completely different.

Rather than invent extremely complex validation logic and add lots of
conditions in the UI for detecting which 'mode' the step is running in
it is much more straight forward to add the same step class multiple times
when defining your steps with different aliases.

You can use the same step any number of times and if you pass arguments to
it's constructor you can control it's 'mode' in a more explicit fashion.

For example consider the case where an address step needs to show as the
user moves through a checkout. But from the confirm step the user might
be invited to make changes to the address before confirming the order.
When the user navigates to change the address, the buttons should be
different. The user should not be offered a 'back to details' button and
the 'forward to payment' button should be replaced with a 'continue' button
that returns them to the confirm step. By providing for these modes and
adding two steps to the step list it is much simpler by design. You can
also acheive the 'mode' variation by extending the step class although
this requires also extending the view in most cases. 

```php
class Checkout extends Wizard
{
    const STEP_PERSONAL = 'personal';
    const STEP_ADDRESS = 'address';
    const STEP_PAYMENT = 'payment';
    const STEP_CONFIRM = 'confirm';
    const STEP_CHANGE_ADDRESS = 'change-address';
    
    protected function createSteps(): array
    {
        return [
            self::STEP_PERSONAL => new PersonalDetailsStep(),
            self::STEP_ADDRESS => new AddressStep(false), // Not in 'change' mode
            self::STEP_PAYMENT => new PaymentStep().
            self::STEP_CONFIRM => new ConfirmationStep(),
            self::STEP_CHANGE_ADDRESS => new AddressStep(true)   // In 'change' mode
        ];
    }
}
```