<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or defined('TYPO3_MODE') or exit('Access denied');

(static function () {
    ExtensionManagementUtility::addStaticFile(
        'form_double_opt_in',
        'Configuration/TypoScript',
        'Form double opt-in'
    );
})();
