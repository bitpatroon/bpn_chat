<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2021 Sjoerd Zonneveld  <code@bitpatroon.nl>
 *  Date: 22-5-2021 17:00
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

use BPN\BpnChat\Domain\Repository\FrontEndUserRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

trait FrontEndUserTrait
{
    /** @var FrontEndUserRepository */
    protected $frontEndUserRepository;

    public function injectFrontEndUserRepository(FrontEndUserRepository $frontEndUserRepository)
    {
        $this->frontEndUserRepository = $frontEndUserRepository;
    }

    public function getFrontEndUserRepository(): FrontEndUserRepository
    {
        if (!$this->frontEndUserRepository) {
            /* @var FrontEndUserRepository $frontEndUserRepository */
            $this->frontEndUserRepository = GeneralUtility::makeInstance(ObjectManager::class)
                ->get(FrontEndUserRepository::class);
        }

        return $this->frontEndUserRepository;
    }
}
