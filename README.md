# TYPO3 CMS form double opt-in

for TYPO3 CMS >=9.5 and PHP >=7.2

## Installation

```
composer require plan2net/form-double-opt-in
```

## Include configuration

Include the static TypoScript Template for the extension
> Form double opt-in

## Secret key for encryption

The form data is stored encrypted in the database, so
once the extension is installed
create a secret key for [defuse PHP encryption](https://github.com/defuse/php-encryption) with

```
$ ./vendor/bin/generate-defuse-key
```

and set the extension option with constants

```
plugin.tx_form.settings {
    doubleOptIn.secretKey = xxxxxx
}
```

or simply set an environment variable in your setup if you have dotenv enabled

```
TYPO3_DEFUSE_KEY=def000005b118015e…
```

(but don't set this anywhere in your database, otherwise an attacker has the key and the data)

## Signals

The form finisher and the controller dispatch the following signals:

- afterOptInCreation
- afterOptInConfirmation

each time with the double opt-in record as argument.

Register your signal processing method in `ext_localconf.php` like

```
/** @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher */
$signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
$signalSlotDispatcher->connect(
    \Plan2net\FormDoubleOptIn\Controller\DoubleOptInController::class,
    \Plan2net\FormDoubleOptIn\Controller\DoubleOptInController::SIGNAL_AFTER_OPT_IN_CONFIRMATION,
    \Vendor\YourExtension\Slots\DoubleOptInControllerSlot::class,
    'afterOptInConfirmationSlot',
    true
);
```

## Fetching original form values

In your signal processing method you can access the original form data (decrypted) through the given argument

```
use Plan2net\FormDoubleOptIn\Domain\Model\FormDoubleOptIn;
class DoubleOptInControllerSlot 
{
…
    public function afterOptInConfirmationSlot(FormDoubleOptIn $doubleOptIn): void
    {
        $formValues = $doubleOptIn->getFormValues();
…
    }
}
```

