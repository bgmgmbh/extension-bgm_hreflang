<?php

namespace BGM\BgmHreflang\Event;

use BGM\BgmHreflang\Utility\HreflangTags;

abstract class AbstractEvent {
    protected HreflangTags $hreflangTagsObject;

    public function __construct(HreflangTags &$hreflangTagsObject) {
        $this->hreflangTagsObject = $hreflangTagsObject;
    }

    public function getHreflangTagsObject(): HreflangTags
    {
        return $this->hreflangTagsObject;
    }

    public function setHreflangTagsObject(HreflangTags $hreflangTagsObject): void
    {
        $this->hreflangTagsObject = $hreflangTagsObject;
    }
}
