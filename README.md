# TYPO3 CMS form double opt-in

for TYPO3 CMS >=12.5

## Installation

```
composer require plan2net/form-double-opt-in
```

## Include configuration

Include the static TypoScript Template for the extension
> Form double opt-in

## Events

The form finisher and the controller dispatch the following events:

- AfterDoubleOptInCreation
- AfterDoubleOptInConfirmation

Register your event processing in `Services.yaml` like

```yaml
  Plan2net\Project\EventListener\AfterDoubleOptInController:
    tags:
      - name: event.listener
        identifier: 'AfterDoubleOptInController'
        event: 'Plan2net\FormDoubleOptIn\Event\AfterDoubleOptInConfirmation'
  Plan2net\Project\EventListener\AfterDoubleOptInFinisher:
    tags:
      - name: event.listener
        identifier: 'AfterDoubleOptInFinisher'
        event: 'Plan2net\FormDoubleOptIn\Event\AfterDoubleOptInCreation'
```

## Fetching original form values

In your event processing method you can access the original form data (decrypted) through the given argument

```php
use Plan2net\FormDoubleOptIn\Event\AfterDoubleOptInConfirmation;

class AfterDoubleOptInController
{
…
    public function __invoke(AfterDoubleOptInConfirmation $afterDoubleOptInConfirmation): void
    {
        $formValues = $afterDoubleOptInConfirmation->getFormValues();
…
    }
}    
```
