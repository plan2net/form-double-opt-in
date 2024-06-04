<?php

declare(strict_types=1);

namespace Plan2net\FormDoubleOptIn\Controller;

use Plan2net\FormDoubleOptIn\Domain\Model\FormDoubleOptIn;
use Plan2net\FormDoubleOptIn\Domain\Repository\FormDoubleOptInRepository;
use Plan2net\FormDoubleOptIn\Event\AfterDoubleOptInConfirmation;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Class DoubleOptInController
 *
 * @author Wolfgang Klinger <wk@plan2.net>
 */
class DoubleOptInController extends ActionController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly FormDoubleOptInRepository $doubleOptInRepository,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function confirmationAction(): ResponseInterface
    {
        $confirmed = false;

        if (!$this->request->hasArgument('hash')) {
            throw new NoSuchArgumentException('hash');
        }

        $hash = $this->request->getArgument('hash');
        $this->setupDoubleOptInRepository();

        /** @var FormDoubleOptIn $doubleOptIn */
        $doubleOptIn = $this->doubleOptInRepository->findOneByConfirmationHash($hash);
        // Matching record found
        if (null !== $doubleOptIn) {
            try {
                if (!$doubleOptIn->isConfirmed()) {
                    $this->confirmDoubleOptIn($doubleOptIn);
                    $this->eventDispatcher
                        ->dispatch(AfterDoubleOptInConfirmation::with(
                            $doubleOptIn->getFormValuesAsArray(),
                            $doubleOptIn->getReceiverInformationAsArray())
                        );
                } else {
                    $this->view->assign('alreadyConfirmed', true);
                    $this->view->assign('confirmationDate', $doubleOptIn->getConfirmationDate());
                }
                $confirmed = true;
            } catch (\Throwable $e) {
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

        $this->view->assign('confirmed', $confirmed);

        return $this->htmlResponse();
    }

    /**
     * @throws \RuntimeException
     */
    protected function confirmDoubleOptIn(FormDoubleOptIn $doubleOptIn): void
    {
        try {
            $doubleOptIn->setConfirmed(true);
            $doubleOptIn->setConfirmationDate(time());
            $this->doubleOptInRepository->update($doubleOptIn);
        } catch (\Throwable $e) {
            throw new \RuntimeException(sprintf('Updating double opt-in record failed with: %s', $e->getMessage()));
        }
    }

    protected function setupDoubleOptInRepository(): void
    {
        $querySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
        // Double opt-in records are always stored on the confirmation page
        $querySettings->setStoragePageIds([self::getTypoScriptFrontendController()->id]);
        $this->doubleOptInRepository->setDefaultQuerySettings($querySettings);
    }

    protected static function getTypoScriptFrontendController(): TypoScriptFrontendController
    {
        return $GLOBALS['TSFE'];
    }

    protected function handleError(string $error, string $message, array $context): string
    {
        $this->logger->error($error, $context);

        return $message;
    }
}
