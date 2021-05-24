<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2021 Sjoerd Zonneveld  <code@bitpatroon.nl>
 *  Date: 12-5-2021 13:24
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

namespace BPN\BpnChat\ViewHelpers;

use BPN\BpnChat\Domain\Repository\FrontendUserRepository;
use BPN\BpnChat\Services\AuthorizationService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class FriendlyUserNameViewHelper extends AbstractViewHelper
{
    /**
     * @var FrontendUserRepository
     */
    protected $frontendUserRepository;

    /** @var AuthorizationService */
    protected $authorizationService;

    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('userid', 'int', 'Id of the user');
        $this->registerArgument('fallback', 'array', 'A collection of key (fallbacktext) and values (id list)');
        $this->registerArgument(
            'fallbackifself',
            'string',
            'Your name / reference, if you were the admin. Displays the email / username otherwise'
        );
    }

    public function injectFrontendUserRepository(FrontendUserRepository $frontendUserRepository
    ) {
        $this->frontendUserRepository = $frontendUserRepository;
    }

    public function injectAuthorizationService(AuthorizationService $authorizationService)
    {
        $this->authorizationService = $authorizationService;
    }

    /**
     * Gets variable text by given label.
     */
    public function render(): string
    {
        $userId = (int) trim($this->arguments['userid']);

        if($this->authorizationService->getUserId() === $userId){
            $fallBackYou = trim($this->arguments['fallbackifself']);
            if($fallBackYou){
                return $fallBackYou;
            }
        }

        $user = $this->frontendUserRepository->findByUid($userId);

        if (!$user) {
            return '[user]';
        }

        $result = [
            0 => $user->getFirstName(),
            100 => $user->getLastName(),
        ];

        if ($user->getMiddleName()) {
            $result[50] = $user->getMiddleName();
        }

        $result = implode(' ', $result);
        $result = trim($result);
        if (!$result) {
            $result = $this->getFallBackName($this->arguments['userid']);
            if (!$result) {
                $result = sprintf('[user: %s]', $this->arguments['userid']);
            }
        }

        return $result;
    }

    private function getFallBackName($userid)
    {
        $fallBack = $this->arguments['fallback'];

        if ($fallBack && is_array($fallBack)) {
            foreach ($fallBack as $key => $value) {
                $items = GeneralUtility::intExplode(',', $value);
                if (in_array($userid, $items)) {
                    return $key;
                }
            }
        }

        return '';
    }
}
