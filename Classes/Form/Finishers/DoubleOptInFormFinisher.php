<?php
declare(strict_types=1);

namespace Plan2net\FormDoubleOptIn\Form\Finishers;

use DateTimeInterface;
use Exception;
use Plan2net\FormDoubleOptIn\Domain\Model\FormDoubleOptIn;
use Plan2net\FormDoubleOptIn\Domain\Repository\FormDoubleOptInRepository;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use RuntimeException;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;
use TYPO3\CMS\Form\Domain\Finishers\EmailFinisher;
use TYPO3\CMS\Form\Domain\Finishers\Exception\FinisherException;
use TYPO3\CMS\Form\Domain\Model\FormElements\FormElementInterface;
use TYPO3\CMS\Form\Service\TranslationService;

/**
 * Class DoubleOptInFormFinisher
 *
 * @package Plan2net\FormDoubleOptIn\Form\Finishers
 * @author Wolfgang Klinger <wk@plan2.net>
 */
class DoubleOptInFormFinisher extends EmailFinisher implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const SIGNAL_AFTER_OPT_IN_CREATION = 'afterOptInCreation';

    protected const REQUIRED_OPTIONS = [
        'subject',
        'recipientAddress',
        'senderAddress',
        'confirmationPid'
    ];

    /**
     * @var array
     */
    protected $defaultOptions = [
        'recipientName' => '',
        'senderName' => '',
        'format' => self::FORMAT_PLAINTEXT
    ];

    /**
     * signalSlotDispatcher
     *
     * @var Dispatcher
     */
    protected $signalSlotDispatcher;

    /**
     * @var FormDoubleOptInRepository
     */
    protected $doubleOptInRepository;

    /**
     * @param Dispatcher $dispatcher
     */
    public function injectSignalSlotDispatcher(Dispatcher $dispatcher): void
    {
        $this->signalSlotDispatcher = $dispatcher;
    }

    /**
     * @param FormDoubleOptInRepository $doubleOptInRepository
     */
    public function injectFormDoubleOptInRepository(FormDoubleOptInRepository $doubleOptInRepository): void
    {
        $this->doubleOptInRepository = $doubleOptInRepository;
    }

    /**
     * @inheritDoc
     * @throws RuntimeException
     */
    protected function executeInternal()
    {
        $formRuntime = $this->finisherContext->getFormRuntime();
        $view = $this->initializeStandaloneView($formRuntime);

        $options = $this->parseOptions();

        try {
            $doubleOptIn = $this->createDoubleOptIn($options);
            $this->dispatchSignal($doubleOptIn);
        } catch (Exception $e) {
            return $this->handleError(
                $e->getMessage(),
                TranslationService::getInstance()
                    ->translate('EXT:form_double_opt_in/Resources/Private/Language/locallang.xlf:internalError'),
                [__CLASS__, __METHOD__, __LINE__]
            );
        }

        $persistenceManager = $this->objectManager->get(PersistenceManager::class);
        $persistenceManager->persistAll();

        $view->assign('confirmationHash', $doubleOptIn->getConfirmationHash());
        $view->assign('confirmationPid', (int)$options['confirmationPid']);
        $view->assign('data', $this->getFormValues());

        if ($options['format'] === self::FORMAT_PLAINTEXT) {
            $view->setFormat('txt');
        }
        $recipientCount = $this->sendMailMessage($view->render(), $options);
        if ($recipientCount === 0) {
            return $this->handleError(
                sprintf('Unable to send E-Mail to "%s"', $options['recipientAddress']),
                TranslationService::getInstance()
                    ->translate('EXT:form_double_opt_in/Resources/Private/Language/locallang.xlf:unableToSendMail'),
                [__CLASS__, __METHOD__, __LINE__]
            );
        }

        return null;
    }

    /**
     * @param array $options
     * @return FormDoubleOptIn
     * @throws IllegalObjectTypeException
     */
    protected function createDoubleOptIn(array $options): FormDoubleOptIn
    {
        $doubleOptIn = $this->objectManager->get(FormDoubleOptIn::class);
        $doubleOptIn->setPid((int)$options['confirmationPid']);
        $doubleOptIn->setFormValues($this->getFormValues());
        $doubleOptIn->setEmail($options['recipientAddress']);
        $doubleOptIn->setReceiverInformation([
            'confirmationReceiverAddress' => $options['confirmationReceiverAddress'] ?? '',
            'confirmationReceiverName' => $options['confirmationReceiverName'] ?? '',
            'confirmationSubject' => $options['confirmationSubject'] ?? ''
        ]);

        $this->doubleOptInRepository->add($doubleOptIn);

        return $doubleOptIn;
    }

    /**
     * @return array
     */
    protected function getFormValues(): array
    {
        $values = [];
        foreach ($this->finisherContext->getFormValues() as $identifier => $value) {
            $element = $this->getElementByIdentifier($identifier);
            if (!$element instanceof FormElementInterface) {
                continue;
            }

            if ($value instanceof FileReference) {
                $value = $value->getOriginalResource()->getCombinedIdentifier();
            } elseif (is_array($value)) {
                $value = implode(',', $value);
            } elseif ($value instanceof DateTimeInterface) {
                $format = $elementsConfiguration[$identifier]['dateFormat'] ?? 'U';
                $value = $value->format($format);
            }

            if ($value !== null) {
                $values[$identifier] = $value;
            }
        }

        return $values;
    }

    /**
     * Returns a form element object for a given identifier.
     *
     * @param string $elementIdentifier
     * @return FormElementInterface|null
     */
    protected function getElementByIdentifier(string $elementIdentifier): ?FormElementInterface
    {
        return $this
            ->finisherContext
            ->getFormRuntime()
            ->getFormDefinition()
            ->getElementByIdentifier($elementIdentifier);
    }

    /**
     * @param string $message
     * @param array $options
     * @return int
     */
    protected function sendMailMessage(string $message, array $options): int
    {
        $mailMessage = $this->objectManager->get(MailMessage::class);

        $mailMessage->setFrom([$options['senderAddress'] => $options['senderName']])
            ->setTo([$options['recipientAddress'] => $options['recipientName']])
            ->setSubject($options['subject']);
        if (!empty($options['replyToAddress'])) {
            $mailMessage->setReplyTo($options['replyToAddress']);
        }
        if (!empty($options['carbonCopyAddress'])) {
            $mailMessage->setCc($options['carbonCopyAddress']);
        }
        if (!empty($options['blindCarbonCopyAddress'])) {
            $mailMessage->setBcc($options['blindCarbonCopyAddress']);
        }

        if ($options['format'] === self::FORMAT_PLAINTEXT) {
            $mailMessage->setBody($message, 'text/plain');
        } else {
            $mailMessage->setBody($message, 'text/html');
        }

        return $mailMessage->send();
    }

    /**
     * @return array
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
                throw new FinisherException(
                    sprintf('The option "%s" must be set for the DoubleOptInFinisher.', $key)
                );
            }
            $options[$key] = $option;
        }

        return $options;
    }

    /**
     * @param FormDoubleOptIn $doubleOptIn
     * @throws RuntimeException
     */
    protected function dispatchSignal(FormDoubleOptIn $doubleOptIn): void
    {
        try {
            $this->signalSlotDispatcher->dispatch(
                __CLASS__,
                self::SIGNAL_AFTER_OPT_IN_CREATION,
                [$doubleOptIn]
            );
        } catch (Exception $e) {
            throw new RuntimeException(
                sprintf('Calling slot dispatcher afterOptInCreation failed with: %s', $e->getMessage())
            );
        }
    }

    /**
     * @param string $error
     * @param string $message
     * @param array $context
     * @return string
     */
    protected function handleError(string $error, string $message, array $context): string
    {
        $this->logger->error($error, $context);
        $this->finisherContext->cancel();

        return $message;
    }
}
