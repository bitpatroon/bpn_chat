<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2021 Sjoerd Zonneveld  <code@bitpatroon.nl>
 *  Date: 24-5-2021 17:34
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

use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

trait PageRendererTrait
{
    /** @var PageRenderer */
    protected $pageRenderer;

    public function injectPageRenderer(PageRenderer $pageRenderer)
    {
        $this->pageRenderer = $pageRenderer;
    }

    public function getPageRenderer(): PageRenderer
    {
        if (!$this->pageRenderer) {
            $this->pageRenderer = GeneralUtility::makeInstance(ObjectManager::class)
                ->get(PageRenderer::class);
        }

        return $this->pageRenderer;
    }

    protected function addCSSFile(string $fileRelativeToPublicRoot)
    {
        $this->getPageRenderer()->addCssFile($fileRelativeToPublicRoot);
    }

    protected function addJsFooterFile(string $fileRelativeToPublicRoot)
    {

        $this->getPageRenderer()->addJsFooterFile($fileRelativeToPublicRoot);
    }

    protected function addJsFooterLibrary(string $fileRelativeToPublicRoot)
    {
        $this->getPageRenderer()->addJsFooterLibrary($this->getName($fileRelativeToPublicRoot), $fileRelativeToPublicRoot);
    }

    protected function addJsFooterInlineCode(string $fileRelativeToPublicRoot)
    {
        $this->getPageRenderer()->addJsFooterInlineCode($this->getName($fileRelativeToPublicRoot), $fileRelativeToPublicRoot);
    }

    private function getName(string $file)
    {
        return md5($file);
    }
}
