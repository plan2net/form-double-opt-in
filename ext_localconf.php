<?php

defined('TYPO3') or defined('TYPO3_MODE') or die ('Access denied.');

(static function () {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'Plan2net.FormDoubleOptIn',
        'DoubleOptIn',
        [
            'DoubleOptIn' => 'confirmation'
        ],
        // Uncached actions
        [
            'DoubleOptIn' => 'confirmation'
        ]
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
