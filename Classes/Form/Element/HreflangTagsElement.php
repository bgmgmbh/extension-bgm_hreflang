<?php
namespace BGM\BgmHreflang\Form\Element;

use BGM\BgmHreflang\Utility\HreflangTags;
use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class HreflangTagsElement extends AbstractFormElement {
    public function render()
    {
        /** @var HreflangTags $hreflangUtility */
        $hreflangUtility = GeneralUtility::makeInstance(HreflangTags::class);

        $result = $this->initializeResultArray();
        $result['html'] = $hreflangUtility->renderBackendList($this->data['databaseRow']['uid']);
        return $result;
    }
}