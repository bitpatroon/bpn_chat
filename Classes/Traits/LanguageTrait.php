<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2021 Sjoerd Zonneveld  <code@bitpatroon.nl>
 *  Date: 24-5-2021 14:31
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

namespace BPN\BpnChat\Traits;

use RuntimeException;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

trait LanguageTrait
{
    /** @var LanguageService */
    protected $languageService;

    private $languageFile = 'LLL:EXT:bpn_chat/Resources/Private/Language/locallang.xlf';

    public function injectLanguageService(LanguageService $languageService)
    {
        $this->languageService = $languageService;
    }

    protected function getLanguageService()
    {
        if (!$this->languageService) {
            /** @var LanguageService $languageService */
            $languageService = GeneralUtility::makeInstance(ObjectManager::class)
                ->get(LanguageService::class);

            $this->languageService = $languageService;
        }

        return $this->languageService;
    }

    protected function translate(string $key, bool $keyIfEmpty = false): string
    {
        if (false === strpos($key, ':')) {
            if ($this->languageFile) {
                $key = $this->languageFile.':'.$key;
            }
        }

        $newValue = $this->sL($key);
        if (!$newValue && $keyIfEmpty) {
            $parts = explode(':', $key);
            $key = end($parts);
            $newValue = '['.$key.']';
        }

        return $newValue;
    }

    public function setLanguageFile(string $languageFile)
    {
        if (!strpos($languageFile, 'LLL:EXT:')) {
            if (!file_exists($languageFile)) {
                throw new RuntimeException('', 1621859965);
            }
        }

        $this->languageFile = $languageFile;
    }

    /**
     * @see \TYPO3\CMS\Core\Localization\LanguageService::sL
     */
    protected function sL(string $input): string
    {
        $parts = explode(':', $input);
        $key = end($parts);

        // return translation  or original key
        return $this->getLanguageService()->sL($input) ?? '['.$key.']';
    }
}
