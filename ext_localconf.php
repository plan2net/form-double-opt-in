<?php

use Plan2net\FormDoubleOptIn\Controller\DoubleOptInController;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or defined('TYPO3_MODE') or exit('Access denied.');

(static function () {
    ExtensionUtility::configurePlugin(
        'FormDoubleOptIn',
        'DoubleOptIn',
        [
            DoubleOptInController::class => 'confirmation'
        ],
        // Uncached actions
        [
            DoubleOptInController::class => 'confirmation'
        ]
    );

    ExtensionManagementUtility::addPageTSConfig(
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
