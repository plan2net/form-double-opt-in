<?php

defined('TYPO3') or defined('TYPO3_MODE') or die ('Access denied.');

(static function () {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
        'form_double_opt_in',
        'DoubleOptIn',
        'LLL:EXT:form_double_opt_in/Resources/Private/Language/locallang.xlf:plugin.doubleoptin'
    );
    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['formdoubleoptin_doubleoptin'] = 'recursive,pages';
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_formdoubleoptin_domain_model_formdoubleoptin');
})();

// https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/12.0/Deprecation-98487-ExtensionManagementUtilityallowTableOnStandardPages.html
// $GLOBALS['TCA']['tt_content']['ctrl']['security']['ignorePageTypeRestriction'] = 'tx_formdoubleoptin_domain_model_formdoubleoptin';
