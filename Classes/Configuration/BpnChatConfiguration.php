<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2021 Sjoerd Zonneveld  <code@bitpatroon.nl>
 *  Date: 6-5-2021 21:38
 *
 *  All rights reserved
 *
 *  This script is part of a Bitpatroon project. The project is
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

namespace BPN\BpnChat\Configuration;

use BPN\BpnChat\Domain\Repository\FrontendUserRepository;
use BPN\Configuration\Configuration\AbstractExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;

class BpnChatConfiguration extends AbstractExtensionConfiguration
{
    /**
     * @var string
     */
    protected $pluginName = 'chat';

    /**
     * @var string
     */
    protected $receivers;

    /**
     * @var \BPN\BpnChat\Domain\Model\FrontendUser[]
     */
    protected $receiverModels;
    /**
     * @var FrontendUserRepository
     */
    protected $frontendUserRepository;

    /**
     * BpnChatConfiguration constructor.
     */
    public function __construct(FrontendUserRepository $frontendUserRepository)
    {
        $this->frontendUserRepository = $frontendUserRepository;
    }


    /**
     * Initializes the application configuration.
     *
     * @param array $settings
     */
    protected function initializeApplication($settings)
    {
        $this->receivers = $this->getRequiredValueFromSettings(
            $settings,
            'receivers',
            'Please set default receivers for this plugin.',
            1620745158
        );

        /** @var QuerySettingsInterface $querySettingsInterface */
        $defaultQuerySettings = GeneralUtility::makeInstance(ObjectManager::class)
            ->get(QuerySettingsInterface::class);
        $defaultQuerySettings->setRespectStoragePage(false);
        $this->frontendUserRepository->setDefaultQuerySettings($defaultQuerySettings);
    }

    /**
     * @return \BPN\BpnChat\Domain\Model\FrontendUser[]
     */
    public function getReceivers()
    {
        if ($this->receiverModels === null) {
            $receivers = GeneralUtility::intExplode(',', $this->receivers);

            $this->receiverModels = !$this->receivers
                ? []
                : $this->frontendUserRepository->getUsersByIds($receivers);
        }

        return $this->receiverModels;
    }

    /**
     * @return int[]
     */
    public function getReceiverIds()
    {
        return GeneralUtility::intExplode(',', $this->receivers);
    }

}
