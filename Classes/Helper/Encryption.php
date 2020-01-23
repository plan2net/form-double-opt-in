<?php
declare(strict_types=1);

namespace Plan2net\FormDoubleOptIn\Helper;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Exception\BadFormatException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Defuse\Crypto\Key;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class Encryption
 *
 * @package Plan2net\FormDoubleOptIn\Helper
 * @author Wolfgang Klinger <wk@plan2.net>
 */
class Encryption
{
    /**
     * @param string $text
     * @return string|null
     */
    public static function encrypt(string $text): ?string
    {
        $key = null;
        try {
            $key = Key::loadFromAsciiSafeString(self::getSecretKey());
        } catch (BadFormatException $e) {
        } catch (EnvironmentIsBrokenException $e) {
        }
        if ($key) {
            try {
                return Crypto::encrypt($text, $key);
            } catch (EnvironmentIsBrokenException $e) {
            }
        }

        return null;
    }

    /**
     * @param string $text
     * @return string|null
     */
    public static function decrypt(string $text): ?string
    {
        $key = null;
        try {
            $key = Key::loadFromAsciiSafeString(self::getSecretKey());
        } catch (BadFormatException $e) {
        } catch (EnvironmentIsBrokenException $e) {
        }
        if ($key) {
            $values = null;
            try {
                return Crypto::decrypt($text, $key);
            } catch (EnvironmentIsBrokenException $e) {
            } catch (WrongKeyOrModifiedCiphertextException $e) {
            }
        }

        return null;
    }

    /**
     * @return string|null
     */
    protected static function getSecretKey(): ?string
    {
        $settings = self::getTypoScriptSettings();

        return $settings['secretKey'] ?? null;
    }

    /**
     * @return array
     */
    protected static function getTypoScriptSettings(): array
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $configurationManager = $objectManager->get(ConfigurationManager::class);
        $configuration = [];
        try {
            $configuration = $configurationManager->getConfiguration(
                \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS,
                'form',
                'formframework'
            );
        } catch (InvalidConfigurationTypeException $e) {
        }

        return $configuration['doubleOptIn'] ?? [];
    }
}