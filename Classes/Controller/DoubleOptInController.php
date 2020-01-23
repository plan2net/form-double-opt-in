<?php
declare(strict_types=1);

namespace Plan2net\FormDoubleOptIn\Controller;

use DateTime;
use Exception;
use Plan2net\FormDoubleOptIn\Domain\Model\FormDoubleOptIn;
use Plan2net\FormDoubleOptIn\Domain\Repository\FormDoubleOptInRepository;
use RuntimeException;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Class DoubleOptInController
 *
 * @package Plan2net\FormDoubleOptIn\Controller
 * @author Wolfgang Klinger <wk@plan2.net>
 */
class DoubleOptInController extends ActionController
{
    public const SIGNAL_AFTER_OPT_IN_CONFIRMATION = 'afterOptInConfirmation';

    /**
     * @var FormDoubleOptInRepository
     */
    protected $doubleOptInRepository;

    /**
     * @param FormDoubleOptInRepository $doubleOptInRepository
     */
    public function injectFormDoubleOptInRepository(FormDoubleOptInRepository $doubleOptInRepository): void
    {
        $this->doubleOptInRepository = $doubleOptInRepository;
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
            $querySettings = $this->objectManager->get(Typo3QuerySettings::class);
            // Double opt-in records are always stored on the confirmation page
            $querySettings->setStoragePageIds([(int)self::getTypoScriptFrontendController()->id]);
            $this->doubleOptInRepository->setDefaultQuerySettings($querySettings);

            /** @var FormDoubleOptIn $doubleOptIn */
            $doubleOptIn = $this->doubleOptInRepository->findOneByConfirmationHash($hash);
            // Matching record found
            if ($doubleOptIn) {
                if (!$doubleOptIn->isConfirmed()) {
                    $this->confirmDoubleOptIn($doubleOptIn);
                    $this->dispatchSignal($doubleOptIn);
                } else {
                    $this->view->assign('alreadyConfirmed', true);
                    $this->view->assign('confirmationDate', $doubleOptIn->getConfirmationDate());
                }

                $confirmed = true;
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
            $this->signalSlotDispatcher->dispatch(
                __CLASS__,
                self::SIGNAL_AFTER_OPT_IN_CONFIRMATION,
                [$doubleOptIn]
            );
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
}