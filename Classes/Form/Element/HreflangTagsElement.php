<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 extension "BGM hreflang".
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace BGM\BgmHreflang\Form\Element;

use BGM\BgmHreflang\Utility\HreflangTags;
use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Form element showing the related pages and their hreflang attributes in the page properties.
 */
class HreflangTagsElement extends AbstractFormElement
{
    /**
     * Renders the list of related pages for the page being edited.
     *
     * @return array<string, mixed>
     */
    public function render(): array
    {
        /** @var HreflangTags $hreflangUtility */
        $hreflangUtility = GeneralUtility::makeInstance(HreflangTags::class);

        $result = $this->initializeResultArray();

        $result['html'] = $hreflangUtility->renderBackendList($this->data['databaseRow']['uid']);

        return $result;
    }
}
