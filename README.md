# TYPO3 CMS form double opt-in

for TYPO3 CMS >=11.5 and PHP >=8.2

## Installation

```
composer require plan2net/form-double-opt-in
```

## Include configuration

Include the static TypoScript Template for the extension
> Form double opt-in

## Events

We are using EventDispatcher (PSR-14 Events) according to https://docs.typo3.org/m/typo3/reference-coreapi/11.5/en-us/ApiOverview/Events/EventDispatcher/Index.html

The form finisher and the controller dispatch the following events:

- AfterOptInCreationEvent
- AfterOptInConfirmationEvent

each time with the double opt-in record as argument.

### Fetching original form values

Register your event listener in `Configuration/Services.yaml` like

```
  Company\ExtensionName\EventListener\AfterOptInCreationEventListener:
    tags:
      - name: event.listener
        identifier: 'AfterOptInCreationEventListener'
        event: Plan2net\FormDoubleOptIn\Event\AfterOptInCreationEvent
```

In your event listerner method you can access the original form data through the given argument

```
use Plan2net\FormDoubleOptIn\Event\AfterOptInConfirmationEvent;
    public function __invoke(AfterOptInConfirmationEvent $event): void
    {
        $doubleOptInData = $event->getDoubleOptIn();
    }
```

