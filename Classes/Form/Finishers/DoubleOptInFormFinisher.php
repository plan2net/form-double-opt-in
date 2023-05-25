<?php

declare(strict_types=1);

namespace Plan2net\FormDoubleOptIn\Form\Finishers;

use Plan2net\FormDoubleOptIn\Domain\Model\FormDoubleOptIn;
use Plan2net\FormDoubleOptIn\Domain\Repository\FormDoubleOptInRepository;
use Plan2net\FormDoubleOptIn\Event\AfterOptInCreationEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Form\Domain\Finishers\EmailFinisher;
use TYPO3\CMS\Form\Domain\Finishers\Exception\FinisherException;
use TYPO3\CMS\Form\Domain\Model\FormElements\FormElementInterface;

/**
 * Class DoubleOptInFormFinisher.
 */
class DoubleOptInFormFinisher extends EmailFinisher implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected const LLL_PATH = 'LLL:EXT:form_double_opt_in/Resources/Private/Language/locallang.xlf:';

    protected const POSSIBLE_OPTIONS = [
        'addHtmlPart',
        'format',
        'confirmationPid',
        'confirmationReceiverAddress',
        'confirmationReceiverName',
        'confirmationSubject',
        'senderAddress',
        'senderName'
    ];

    protected const REQUIRED_OPTIONS = [
        'confirmationPid',
        'confirmationReceiverAddress',
        'confirmationSubject',
        'senderAddress'
    ];

    /**
     * @var array
     *
     * phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    protected $defaultOptions = [
        'addHtmlPart' => false,
        'senderAddress' => '',
        'senderName' => '',
    ];
    // phpcs:enable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint

    protected EventDispatcherInterface $eventDispatcher;

    protected FormDoubleOptInRepository $doubleOptInRepository;

    public function injectEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function injectFormDoubleOptInRepository(FormDoubleOptInRepository $doubleOptInRepository): void
    {
        $this->doubleOptInRepository = $doubleOptInRepository;
    }

    /**
     * {@inheritDoc}
     *
     * @throws \RuntimeException
     */
    protected function executeInternal()
    {
        /** @psalm-suppress InternalMethod */
        $formRuntime = $this->finisherContext->getFormRuntime();
        $view = $this->initializeStandaloneView($formRuntime, 'html');

        try {
            $options = $this->parseOptions();
            $doubleOptIn = $this->createDoubleOptIn($options);
            $this->dispatchEvent($doubleOptIn);
        } catch (\Throwable $e) {
            return $this->handleError(
                $e->getMessage(),
                self::translateLllString(
                    self::LLL_PATH . 'internalError'
                ),
                [__CLASS__, __METHOD__, $e->getLine()]
            );
        }

            $persistenceManager = GeneralUtility::makeInstance(PersistenceManager::class);
        $persistenceManager->persistAll();

        $view->assign('confirmationHash', $doubleOptIn->getConfirmationHash());
        $view->assign('confirmationPid', (int)$options['confirmationPid']);
        $view->assign('data', $this->getFormValues());

        if ($options['addHtmlPart'] === false) {
            $view->setFormat('txt');
        }
        $emailSent = $this->sendMailMessage($view->render(), $options);
        if (!$emailSent) {
            return $this->handleError(
                \sprintf('Unable to send E-Mail to "%s"', $options['recipientAddress']),
                self::translateLllString(self::LLL_PATH . 'unableToSendMail'),
                [__CLASS__, __METHOD__, __LINE__]
            );
        }
    }

    /**
     * @throws IllegalObjectTypeException
     */
    protected function createDoubleOptIn(array $options): FormDoubleOptIn
    {
        $doubleOptIn = GeneralUtility::makeInstance(FormDoubleOptIn::class);
        /** @psalm-suppress InternalMethod */
        $doubleOptIn->setPid((int)$options['confirmationPid']);
        $doubleOptIn->setFormValues($this->getFormValues());
        $doubleOptIn->setEmail($options['confirmationReceiverAddress']);
        $doubleOptIn->setReceiverInformation([
            'confirmationReceiverAddress' => $options['confirmationReceiverAddress'],
            'confirmationReceiverName' => $options['confirmationReceiverName'] ?? '',
            'confirmationSubject' => $options['confirmationSubject'] ?? ''
        ]);

        $this->doubleOptInRepository->add($doubleOptIn);

        return $doubleOptIn;
    }

    protected function getFormValues(): array
    {
        $values = [];
        /** @psalm-suppress InternalMethod */
        foreach ($this->finisherContext->getFormValues() as $identifier => $value) {
            $element = $this->getElementByIdentifier($identifier);
            if (!$element instanceof FormElementInterface) {
                continue;
            }

            if ($value instanceof FileReference) {
                $value = $value->getOriginalResource()->getCombinedIdentifier();
            } elseif (is_array($value)) {
                $value = implode(',', $value);
            } elseif ($value instanceof \DateTimeInterface) {
                // @todo: recheck with v11
                $value = $value->format('U');
            }

            if ($value !== null) {
                $values[$identifier] = $value;
            }
        }

        return $values;
    }

    /**
     * Returns a form element object for a given identifier.
     */
    protected function getElementByIdentifier(string $elementIdentifier): ?FormElementInterface
    {
        /** @psalm-suppress InternalMethod */
        return $this
            ->finisherContext
            ->getFormRuntime()
            ->getFormDefinition()
            ->getElementByIdentifier($elementIdentifier);
    }

    protected function sendMailMessage(string $message, array $options): bool
    {
        $mailMessage = GeneralUtility::makeInstance(MailMessage::class);

        $mailMessage->setFrom([$options['senderAddress'] => $options['senderName']])
            ->setTo([$options['confirmationReceiverAddress'] => $options['confirmationReceiverName']])
            ->setSubject($options['confirmationSubject']);

        if ($options['addHtmlPart'] === false) {
            $mailMessage->text($message);
        } else {
            $mailMessage->html($message);
        }

        return $mailMessage->send();
    }

    /**
     * @throws FinisherException
     */
    protected function parseOptions(): array
    {
        $options = [];
        foreach (self::POSSIBLE_OPTIONS as $key) {
            $option = $this->parseOption($key);
            if (empty($option) && in_array($key, self::REQUIRED_OPTIONS, true)) {
                throw new FinisherException(
                    \sprintf('The option "%s" must be set for the DoubleOptInFinisher.', $key)
                );
            }
            $options[$key] = $option;
        }

        return $options;
    }

    protected function dispatchEvent(FormDoubleOptIn $doubleOptIn): void
    {
        $this->eventDispatcher->dispatch(new AfterOptInCreationEvent($doubleOptIn));
    }

    protected function handleError(string $error, string $message, array $context): string
    {
        $this->logger->error($error, $context);
        /** @psalm-suppress InternalMethod */
        $this->finisherContext->cancel();

        return $message;
    }

    private static function translateLllString(string $lll): string
    {
        $languageServiceFactory = GeneralUtility::makeInstance(
            LanguageServiceFactory::class
        );
        $request = $GLOBALS['TYPO3_REQUEST'];
        $languageService = $languageServiceFactory->createFromSiteLanguage(
            $request->getAttribute('language')
            ?? $request->getAttribute('site')->getDefaultLanguage()
        );

        return $languageService->sL($lll);
    }
}
