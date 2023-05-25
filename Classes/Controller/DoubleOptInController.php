<?php

declare(strict_types=1);

namespace Plan2net\FormDoubleOptIn\Controller;

use Plan2net\FormDoubleOptIn\Domain\Model\FormDoubleOptIn;
use Plan2net\FormDoubleOptIn\Domain\Repository\FormDoubleOptInRepository;
use Plan2net\FormDoubleOptIn\Event\AfterOptInConfirmationEvent;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Class DoubleOptInController.
 */
class DoubleOptInController extends ActionController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected const LL_PATH = 'EXT:form_double_opt_in/Resources/Private/Language/locallang.xlf:';

    public function __construct(
        protected FormDoubleOptInRepository $doubleOptInRepository,
        protected readonly LanguageServiceFactory $languageServiceFactory,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function confirmationAction(): ResponseInterface
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
            $querySettings->setStoragePageIds([(int)self::getTypoScriptFrontendController()->id]);
            $this->doubleOptInRepository->setDefaultQuerySettings($querySettings);

            $doubleOptIn = $this->doubleOptInRepository->findOneByConfirmationHash($hash);
            // Matching record found
            if ($doubleOptIn) {
                try {
                    if (!$doubleOptIn->isConfirmed()) {
                        $this->confirmDoubleOptIn($doubleOptIn);
                        $this->dispatchEvent($doubleOptIn);
                    } else {
                        $this->view->assign('alreadyConfirmed', true);
                        $this->view->assign('confirmationDate', $doubleOptIn->getConfirmationDate());
                    }
                    $confirmed = true;
                } catch (\Throwable $e) {
                    $doubleOptIn->setConfirmed(false);
                    $doubleOptIn->setConfirmationDate(null);
                    $this->doubleOptInRepository->update($doubleOptIn);

                    $languageService = $this->getLanguageService($this->request);
                    $customError = $languageService->sL(self::LL_PATH . 'internalError');
                    $this->view->assign('error', $this->handleError(
                        $e->getMessage(),
                        $customError,
                        [__CLASS__, __METHOD__, __LINE__]
                    ));
                }
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
            $doubleOptIn->setConfirmationDate(new \DateTime('now'));
            $this->doubleOptInRepository->update($doubleOptIn);
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                sprintf('Updating double opt-in record failed with: %s', $e->getMessage())
            );
        }
    }

    protected function dispatchEvent(FormDoubleOptIn $doubleOptIn): void
    {
        $this->eventDispatcher->dispatch(new AfterOptInConfirmationEvent($doubleOptIn));
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

    protected function getLanguageService(
        ServerRequestInterface $request
    ): LanguageService {
        return $this->languageServiceFactory->createFromSiteLanguage(
            $request->getAttribute('language')
            ?? $request->getAttribute('site')->getDefaultLanguage()
        );
    }
}
