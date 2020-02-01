<?php

defined('TYPO3_MODE') or die ('Access denied.');

(static function () {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
        'Plan2net.FormDoubleOptIn',
        'DoubleOptIn',
        'LLL:EXT:form_double_opt_in/Resources/Private/Language/locallang.xlf:plugin.doubleoptin'
    );
    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['formdoubleoptin_doubleoptin'] = 'recursive,pages';
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_formdoubleoptin_domain_model_formdoubleoptin');
})();
