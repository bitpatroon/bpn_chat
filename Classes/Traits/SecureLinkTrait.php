<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2021 Sjoerd Zonneveld  <code@bitpatroon.nl>
 *  Date: 24-5-2021 15:12
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
use TYPO3\CMS\Core\Utility\GeneralUtility;

trait SecureLinkTrait
{
    protected $queryParameters = [];

    protected function generateHash(
        array $arguments
    ): string {
        $queryString = http_build_query($arguments);

        return GeneralUtility::hmac($queryString, $this->getSecret());
    }

    protected function getQuery(array $parameters): string
    {
        $hash = $this->generateHash($parameters);
        $parameters['cs'] = $hash;
        $query = http_build_query($parameters);

        return http_build_query(['c' => base64_decode($query)]);
    }

    protected function getLinkArguments(): array
    {
        $queryString = GeneralUtility::_GP('c');
        if (!$queryString) {
            throw new RuntimeException('result.invalidhash', 1621862010);
        }
        $queryString = base64_decode($queryString);
        if (!$queryString || false === strpos($queryString, '&cs=')) {
            throw new RuntimeException('result.invalidhash', 1621862013);
        }

        parse_str($queryString, $parts);
        if (!$parts) {
            throw new RuntimeException('result.invalidhash', 1621862016);
        }
        $this->queryParameters = $parts;

        return $parts;
    }

    protected function validateUrl(): void
    {
        $parts = $this->getLinkArguments();

        $hash = $parts['cs'];
        unset($parts['cs']);
        $calculatedHash = GeneralUtility::hmac(http_build_query($parts), $this->getSecret());

        if ($calculatedHash !== $hash) {
            throw new RuntimeException('result.invalidhash', 1621862037);
        }
    }

    private function getSecret()
    {
        if (!isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']) || empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'])) {
            throw new RuntimeException(
                'EncryptionKey (TYPO3_CONF_VARS->SYS->encryptionKey) is not and is required',
                1621862392
            );
        }
        $secret = TraitConstants::SECRET;

        return $secret.($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']);
    }

    protected function generateLinkWithHash(array $params, array $hashParams)
    {
        $params['sh'] = $this->generateLinkHash($hashParams);

        $script = GeneralUtility::getIndpEnv('SCRIPT_NAME');
        return $script . '?' . http_build_query($params);
    }

    protected function validateSenderLinkHash(array $hashParams, string $hash)
    {
        $calculatedHash = $this->generateLinkHash($hashParams);
        if ($calculatedHash === $hash) {
            return;
        }
        throw new RuntimeException(
            'Not allowed',
            1621968013
        );
    }

    private function generateLinkHash(array $arguments)
    {
        $ts = time() / (86400 * 2);

        $arguments['dt'] = (int) floor((int) $ts);
        $arguments['key'] = $this->getSecret();

        return md5(implode(',', $arguments));
    }

}
