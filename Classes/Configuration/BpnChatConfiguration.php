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

use BPN\BpnChat\Traits\FrontEndUserTrait;
use BPN\BpnChat\Traits\LanguageTrait;
use BPN\Configuration\Configuration\AbstractExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;

class BpnChatConfiguration extends AbstractExtensionConfiguration
{
    use FrontEndUserTrait;
    use LanguageTrait;

    /**
     * @var string
     */
    protected $pluginName = 'chat';

    /**
     * @var string
     */
    protected $adminIds;

    /** @var int */
    protected $autoUpdateInterval = 0;

    /** @var int */
    protected $pauseBtnEnabled = 0;

    /**
     * @var \BPN\BpnChat\Domain\Model\FrontEndUser[]
     */
    protected $receiverModels;

    /** @var string */
    private $administratorName = 'admin';

    /** @var int */
    private $debug = 0;

    /** @var int */
    private $showDate = 0;

    /** @var string */
    private $offlineMessage = 0;
    /** @var string */
    private $offlineMessageForUser;

    /**
     * Initializes the application configuration.
     *
     * @param array $settings
     */
    protected function initializeApplication($settings)
    {
        $this->adminIds = $this->getRequiredValueFromSettings(
            $settings,
            'receivers',
            'Please set default receivers for this plugin.',
            1620745158
        );

        $this->autoUpdateInterval = (int) $this->getValueFromSettings(
            $settings,
            'auto_update_interval'
        );

        if ($this->autoUpdateInterval < 0) {
            $this->autoUpdateInterval = 0;
        }

        $this->pauseBtnEnabled = (int) $this->getValueFromSettings(
            $settings,
            'pause_btn_enabled'
        );
        $this->pauseBtnEnabled = $this->pauseBtnEnabled ? 1 : 0;

        $administratorName = $this->getValueFromSettings(
            $settings,
            'administrator_name'
        );
        if ($administratorName) {
            $this->administratorName = $administratorName;
        }
        $administratorNameTranslate = (int) $this->getValueFromSettings(
            $settings,
            'administrator_name_translate'
        );
        if ($administratorNameTranslate) {
            $this->administratorName = $this->translate($this->administratorName, true);
        }

        $this->debug = ((int) $this->getValueFromSettings($settings, 'debug')) ? 1 : 0;
        $this->showDate = ((int) $this->getValueFromSettings($settings, 'show_message_dates')) ? 1 : 0;
        $this->offlineMessage = $this->getValueFromSettings($settings, 'offlineMessage');
        $this->offlineMessageForUser = $this->getValueFromSettings($settings, 'offlineMessageForUser');

        /** @var QuerySettingsInterface $querySettingsInterface */
        $defaultQuerySettings = GeneralUtility::makeInstance(ObjectManager::class)
            ->get(QuerySettingsInterface::class);
        $defaultQuerySettings->setRespectStoragePage(false);
        $this->getFrontEndUserRepository()->setDefaultQuerySettings($defaultQuerySettings);
    }

    /**
     * @return \BPN\BpnChat\Domain\Model\FrontEndUser[]
     */
    public function getAdmins()
    {
        if (null === $this->receiverModels) {
            $receivers = GeneralUtility::intExplode(',', $this->adminIds);

            $this->receiverModels = !$this->adminIds
                ? []
                : $this->getFrontEndUserRepository()->getUsersByIds($receivers);
        }

        return $this->receiverModels;
    }

    /**
     * @return int[]
     */
    public function getAdminIds()
    {
        $ids = GeneralUtility::intExplode(',', $this->adminIds);
        $ids = array_combine($ids, $ids);
        $ids[0] = 0;

        return $ids;
    }

    /**
     * @return int[]
     * @deprecated use \BPN\BpnChat\Configuration\BpnChatConfiguration::getAdminIds
     */
    public function getReceiverIds()
    {
        return GeneralUtility::intExplode(',', $this->adminIds);
    }

    public function getAutoUpdateInterval()
    {
        return $this->autoUpdateInterval ?? 10;
    }

    public function userIsAnAdmin(int $userId): bool
    {
        $adminIds = $this->getAdminIds();

        return in_array($userId, $adminIds);
    }

    public function getPauseBtnEnabled(): int
    {
        return $this->pauseBtnEnabled;
    }

    public function getAdministratorName()
    {
        return $this->administratorName;
    }

    public function getDebug(): int
    {
        return $this->debug ?? 0;
    }

    public function getShowDate(): int
    {
        return $this->showDate ?? 0;
    }

    /**
     * @return string
     */
    public function getOfflineMessage()
    {
        return $this->offlineMessage;
    }

    /**
     * @return string
     */
    public function getOfflineMessageForUser()
    {
        return $this->offlineMessageForUser;
    }
}
