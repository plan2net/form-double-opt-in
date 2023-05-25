<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') || exit('Access denied');

(static function (): void {
    ExtensionManagementUtility::addStaticFile(
        'form_double_opt_in',
        'Configuration/TypoScript',
        'Form double opt-in'
    );
})();
