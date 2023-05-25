<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') || exit('Access denied.');

(static function (): void {
    ExtensionUtility::registerPlugin(
        'FormDoubleOptIn',
        'DoubleOptIn',
        'LLL:EXT:form_double_opt_in/Resources/Private/Language/locallang.xlf:plugin.doubleoptin'
    );
    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['formdoubleoptin_doubleoptin'] = 'recursive,pages';
    ExtensionManagementUtility::allowTableOnStandardPages('tx_formdoubleoptin_domain_model_formdoubleoptin');
})();
