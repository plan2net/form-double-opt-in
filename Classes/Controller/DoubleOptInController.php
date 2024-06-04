<?php
declare(strict_types=1);

namespace Plan2net\FormDoubleOptIn\Controller;

use DateTime;
use Exception;
use Plan2net\FormDoubleOptIn\Event\AfterDoubleOptInConfirmation;
use Plan2net\FormDoubleOptIn\Domain\Model\FormDoubleOptIn;
use Plan2net\FormDoubleOptIn\Domain\Repository\FormDoubleOptInRepository;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use RuntimeException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Class DoubleOptInController
 *
 * @package Plan2net\FormDoubleOptIn\Controller
 * @author Wolfgang Klinger <wk@plan2.net>
 */
class DoubleOptInController extends ActionController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private FormDoubleOptInRepository $doubleOptInRepository,
    ) {
    }

    /**
     * @throws Exception
     */
    public function confirmationAction(): void
    {
        $confirmed = false;

        $hash = null;
        try {
            $hash = $this->request->getArgument('hash');
        } catch (NoSuchArgumentException $e) {
        }
        if ($hash) {
            $querySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
            // Double opt-in records are always stored on the confirmation page
            $querySettings->setStoragePageIds([self::getTypoScriptFrontendController()->id]);
            $this->doubleOptInRepository->setDefaultQuerySettings($querySettings);

            /** @var FormDoubleOptIn $doubleOptIn */
            $doubleOptIn = $this->doubleOptInRepository->findOneByConfirmationHash($hash);
            // Matching record found
            if ($doubleOptIn) {
                try {
                    if (!$doubleOptIn->isConfirmed()) {
                        $this->confirmDoubleOptIn($doubleOptIn);
                        $this->dispatchSignal($doubleOptIn);
                    } else {
                        $this->view->assign('alreadyConfirmed', true);
                        $this->view->assign('confirmationDate', $doubleOptIn->getConfirmationDate());
                    }
                    $confirmed = true;
                } catch (Exception $e) {
                    $doubleOptIn->setConfirmed(false);
                    $doubleOptIn->setConfirmationDate(0);
                    $this->doubleOptInRepository->update($doubleOptIn);

                    $this->view->assign('error', $this->handleError(
                        $e->getMessage(),
                        LocalizationUtility::translate('internalError', 'form_double_opt_in'),
                        [__CLASS__, __METHOD__, __LINE__]
                    ));
                }
            }
        }

        $this->view->assign('confirmed', $confirmed);
    }

    /**
     * @param FormDoubleOptIn $doubleOptIn
     * @throws RuntimeException
     */
    protected function confirmDoubleOptIn(FormDoubleOptIn $doubleOptIn): void
    {
        try {
            $doubleOptIn->setConfirmed(true);
            $doubleOptIn->setConfirmationDate(new DateTime('now'));
            $this->doubleOptInRepository->update($doubleOptIn);
        } catch (Exception $e) {
            throw new RuntimeException(
                sprintf('Updating double opt-in record failed with: %s', $e->getMessage())
            );
        }
    }

    /**
     * @param FormDoubleOptIn $doubleOptIn
     * @throws RuntimeException
     */
    protected function dispatchSignal(FormDoubleOptIn $doubleOptIn): void
    {
        try {
            $this->eventDispatcher->dispatch(AfterDoubleOptInConfirmation::with($doubleOptIn));
        } catch (Exception $e) {
            throw new RuntimeException(
                sprintf('Calling slot dispatcher afterOptInConfirmation failed with: %s', $e->getMessage())
            );
        }
    }

    /**
     * @return TypoScriptFrontendController
     */
    protected static function getTypoScriptFrontendController(): TypoScriptFrontendController
    {
        return $GLOBALS['TSFE'];
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

        return $message;
    }
}