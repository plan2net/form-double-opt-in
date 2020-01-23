<?php

defined('TYPO3_MODE') or die('Access denied');

(static function () {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
        'form_double_opt_in',
        'Configuration/TypoScript',
        'Form double opt-in'
    );
})();
