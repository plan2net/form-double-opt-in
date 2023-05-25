<?php

declare(strict_types=1);

defined('TYPO3') || exit('Access denied.');

(static function (): void {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'FormDoubleOptIn',
        'DoubleOptIn',
        [
            \Plan2net\FormDoubleOptIn\Controller\DoubleOptInController::class => 'confirmation'
        ],
        // Uncached actions
        [
            \Plan2net\FormDoubleOptIn\Controller\DoubleOptInController::class => 'confirmation'
        ]
    );

    $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        \TYPO3\CMS\Core\Imaging\IconRegistry::class
    );
    $iconRegistry->registerIcon(
        'plugin-doubleoptin',
        \TYPO3\CMS\Core\Imaging\IconProvider\FontawesomeIconProvider::class,
        ['name' => 'wpforms']
    );

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
        'mod {
                wizards.newContentElement.wizardItems.plugins {
                    elements {
                        formdoubleoptin_doubleoptin {
                            iconIdentifier = plugin-doubleoptin
                            title = Double Opt-In Confirmation
                            description = Validation and confirmation of double opt-in form submit
                            tt_content_defValues {
                                CType = list
                                list_type = formdoubleoptin_doubleoptin
                            }
                        }
                    }
                    show := addToList(formdoubleoptin_doubleoptin)
                }
           }'
    );
})();
