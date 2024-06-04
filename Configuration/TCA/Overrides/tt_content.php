<?php

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or defined('TYPO3_MODE') or exit('Access denied.');

(static function () {
    ExtensionUtility::registerPlugin(
        'form_double_opt_in',
        'DoubleOptIn',
        'LLL:EXT:form_double_opt_in/Resources/Private/Language/locallang.xlf:plugin.doubleoptin'
    );
    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['formdoubleoptin_doubleoptin'] = 'recursive,pages';
    $GLOBALS['TCA']['tt_content']['ctrl']['security']['ignorePageTypeRestriction'] = 'tx_formdoubleoptin_domain_model_formdoubleoptin';
})();
