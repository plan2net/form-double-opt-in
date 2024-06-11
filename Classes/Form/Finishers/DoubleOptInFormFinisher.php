<?php

declare(strict_types=1);

namespace Plan2net\FormDoubleOptIn\Form\Finishers;

use Plan2net\FormDoubleOptIn\Domain\Model\FormDoubleOptIn;
use Plan2net\FormDoubleOptIn\Domain\Repository\FormDoubleOptInRepository;
use Plan2net\FormDoubleOptIn\Event\AfterDoubleOptInCreation;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Address;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Mail\MailerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Form\Domain\Finishers\EmailFinisher;
use TYPO3\CMS\Form\Domain\Finishers\Exception\FinisherException;
use TYPO3\CMS\Form\Domain\Model\FormElements\FormElementInterface;
use TYPO3\CMS\Form\Service\TranslationService;

/**
 * Class DoubleOptInFormFinisher
 *
 * @author Wolfgang Klinger <wk@plan2.net>
 */
class DoubleOptInFormFinisher extends EmailFinisher implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected const FORMAT_PLAINTEXT = 'plaintext';

    protected const REQUIRED_OPTIONS = [
        'recipientAddress',
        'senderAddress',
        'confirmationPid'
    ];

    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private FormDoubleOptInRepository $formDoubleOptInRepository,
    ) {
    }

    /**
     * @throws \RuntimeException
     * @throws TransportExceptionInterface
     * @throws FinisherException
     */
    protected function executeInternal()
    {
        $options = $this->parseOptions();

        try {
            $doubleOptIn = $this->createDoubleOptIn($options);
            $processedFormData = $this->eventDispatcher->dispatch(AfterDoubleOptInCreation::with($doubleOptIn->getFormValuesAsArray()));
            if ($processedFormData) {
                $doubleOptIn->setFormValuesAs($processedFormData->getFormValues());
            }
        } catch (\Throwable $e) {
            return $this->handleError(
                $e->getMessage(),
                LocalizationUtility::translate('internalError', 'form_double_opt_in'),
                [__CLASS__, __METHOD__, __LINE__]
            );
        }

        $persistenceManager = GeneralUtility::makeInstance(PersistenceManager::class);
        $persistenceManager->persistAll();

        $subject = (string) $this->parseOption('subject');
        $recipientAddress = (string) $this->parseOption('recipientAddress');
        $recipientName = (string) $this->parseOption('recipientName');
        $recipients = [new Address($recipientAddress, $recipientName)];
        $senderAddress = $this->parseOption('senderAddress');
        $senderAddress = is_string($senderAddress) ? $senderAddress : '';
        $senderName = $this->parseOption('senderName');
        $senderName = is_string($senderName) ? $senderName : '';
        $replyToRecipients = $this->getRecipients('replyToRecipients');
        $carbonCopyRecipients = $this->getRecipients('carbonCopyRecipients');
        $blindCarbonCopyRecipients = $this->getRecipients('blindCarbonCopyRecipients');
        $format = $this->parseOption('format');
        $title = (string) $this->parseOption('title') ?: $subject;

        if ('' === $subject) {
            throw new FinisherException('The option "subject" must be set for the EmailFinisher.', 1327060320);
        }
        if (empty($recipients)) {
            throw new FinisherException('The option "recipients" must be set for the EmailFinisher.', 1327060200);
        }
        if (empty($senderAddress)) {
            throw new FinisherException('The option "senderAddress" must be set for the EmailFinisher.', 1327060210);
        }

        /** @psalm-suppress InternalMethod */
        $formRuntime = $this->finisherContext->getFormRuntime();

        $translationService = GeneralUtility::makeInstance(TranslationService::class);
        /** @psalm-suppress InternalMethod */
        if (is_string($this->options['translation']['language'] ?? null) && '' !== $this->options['translation']['language']) {
            /** @psalm-suppress InternalMethod */
            $languageBackup = $translationService->getLanguage();
            /** @psalm-suppress InternalMethod */
            $translationService->setLanguage($this->options['translation']['language']);
        }

        $mail = $this
            ->initializeFluidEmail($formRuntime)
            ->from(new Address($senderAddress, $senderName))
            ->to(...$recipients)
            ->subject($subject)
            ->format(self::FORMAT_PLAINTEXT === $format ? FluidEmail::FORMAT_PLAIN : FluidEmail::FORMAT_BOTH)
            ->assign('title', $title)
            ->assign('confirmationHash', $doubleOptIn->getConfirmationHash())
            ->assign('confirmationPid', (int) $options['confirmationPid'])
            ->assign('data', $this->getFormValues());

        if (!empty($replyToRecipients)) {
            $mail->replyTo(...$replyToRecipients);
        }

        if (!empty($carbonCopyRecipients)) {
            $mail->cc(...$carbonCopyRecipients);
        }

        if (!empty($blindCarbonCopyRecipients)) {
            $mail->bcc(...$blindCarbonCopyRecipients);
        }

        if (!empty($languageBackup)) {
            /** @psalm-suppress InternalMethod */
            $translationService->setLanguage($languageBackup);
        }

        $temp = GeneralUtility::makeInstance(MailerInterface::class);
        $temp->send($mail);

        $recipientCount = $temp->getSentMessage()?->getEnvelope()->getRecipients();
        if (0 === $recipientCount) {
            return $this->handleError(
                sprintf('Unable to send E-Mail to "%s"', $options['recipientAddress']),
                LocalizationUtility::translate('unableToSendMail', 'form_double_opt_in'),
                [__CLASS__, __METHOD__, __LINE__]
            );
        }

        return null;
    }

    /**
     * @throws IllegalObjectTypeException|\JsonException
     */
    protected function createDoubleOptIn(array $options): FormDoubleOptIn
    {
        $doubleOptIn = GeneralUtility::makeInstance(FormDoubleOptIn::class);
        $doubleOptIn->setPid((int) $options['confirmationPid']);
        $doubleOptIn->setFormValuesAs($this->getFormValues());
        $doubleOptIn->setEmail($options['recipientAddress']);
        $doubleOptIn->setReceiverInformationAs([
            'confirmationReceiverAddress' => $options['confirmationReceiverAddress'] ?? '',
            'confirmationReceiverName' => $options['confirmationReceiverName'] ?? '',
            'confirmationSubject' => $options['confirmationSubject'] ?? ''
        ]);

        $this->formDoubleOptInRepository->add($doubleOptIn);

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
                $value = $value->format('U');
            }

            if (null !== $value) {
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

    /**
     * @throws FinisherException
     */
    protected function parseOptions(): array
    {
        $options = [];
        foreach ([
            'subject',
            'recipientAddress',
            'recipientName',
            'senderAddress',
            'senderName',
            'replyToAddress',
            'carbonCopyAddress',
            'blindCarbonCopyAddress',
            'format',
            'confirmationSubject',
            'confirmationReceiverAddress',
            'confirmationReceiverName',
            'confirmationPid'
        ] as $key) {
            $option = $this->parseOption($key);
            if (empty($option) && in_array($key, self::REQUIRED_OPTIONS, true)) {
                throw new FinisherException(sprintf('The option "%s" must be set for the DoubleOptInFinisher.', $key));
            }
            $options[$key] = $option;
        }

        return $options;
    }

    protected function handleError(string $error, string $message, array $context): string
    {
        $this->logger->error($error, $context);
        /** @psalm-suppress InternalMethod */
        $this->finisherContext->cancel();

        return $message;
    }
}
