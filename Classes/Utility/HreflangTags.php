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

namespace BGM\BgmHreflang\Utility;

use BGM\BgmHreflang\Event\BackendAfterRenderEvent;
use BGM\BgmHreflang\Event\BackendAfterRenderSingleHreflangAttributeEvent;
use BGM\BgmHreflang\Event\BackendAfterRenderSinglePageEvent;
use BGM\BgmHreflang\Event\BackendBeforeRenderSingleHreflangAttributeEvent;
use BGM\BgmHreflang\Event\BackendBeforeRenderSinglePageEvent;
use BGM\BgmHreflang\Event\BuildHreflangAttributesEvent;
use BGM\BgmHreflang\Event\FrontendAfterRenderEvent;
use BGM\BgmHreflang\Event\FrontendAfterRenderSingleTagEvent;
use BGM\BgmHreflang\Event\FrontendBeforeRenderSingleTagEvent;
use BGM\BgmHreflang\Service\RelatedPages;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Builds hreflang relations of pages and renders them as backend list or frontend link tags.
 */
class HreflangTags implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected ConnectionPool $connectionPool;
    protected EventDispatcher $eventDispatcher;

    /**
     * current $_GET parameters
     *
     * @see renderBackendList(), renderFrontendList()
     */
    protected array $getParameters;

    /**
     * current related page
     *
     * @see renderBackendList(), renderFrontendList()
     */
    protected int $relatedPage = 0;

    /**
     * current hreflang attribute for the related page
     *
     * @see renderBackendList(), renderFrontendList()
     */
    protected string $hreflangAttribute;

    /**
     * curent hreflang attributes for the related page
     *
     * @see renderBackendList(), renderFrontendList()
     */
    protected array $hreflangAttributes;

    /**
     * additional parameters for the current hreflang attribute $hreflangAttribute.
     * contains the keys sysLanguageUid and mountPoint
     *
     * @see renderBackendList(), renderFrontendList()
     */
    protected array $additionalParameters = [];

    /**
     * rendered item
     *
     * @see renderBackendList(), renderFrontendList()
     */
    protected string $renderedListItem;

    /**
     * rendered items
     *
     * @see renderBackendList(), renderFrontendList()
     */
    protected array $renderedListItems;

    /**
     * rendered list
     *
     * @see renderBackendList(), renderFrontendList()
     */
    protected string $renderedList;

    /**
     * valid relation
     *
     * @see renderBackendList(), renderFrontendList()
     */
    protected bool $validRelation;

    /**
     * Fetches the connection pool and the event dispatcher.
     */
    public function __construct()
    {
        $this->connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $this->eventDispatcher = GeneralUtility::makeInstance(EventDispatcher::class);
    }

    /**
     * Renders the related pages of a page with their hreflang attributes for the backend.
     *
     * @param int|string $uid Page uid, may be a placeholder like NEW123 for new records
     */
    public function renderBackendList($uid)
    {
        $this->renderedList = '';
        $this->renderedListItems = [];
        if ((int)$uid > 0) {
            $relations = $this->getCachedRelations((int)$uid);

            foreach ($relations as $this->relatedPage => $info) {
                $this->dispatch(new BackendBeforeRenderSinglePageEvent($this));

                $this->renderedListItem = '<li>' . BackendUtility::getRecordPath($this->relatedPage, '', 1000) . ' [' . $this->relatedPage . ']';
                $this->hreflangAttributes = [];
                foreach ($info as $this->hreflangAttribute => $this->additionalParameters) {
                    $this->validRelation = true;
                    $this->dispatch(new BackendBeforeRenderSingleHreflangAttributeEvent($this));
                    if ($this->validRelation) {
                        $this->hreflangAttributes[] = '<li>' . $this->hreflangAttribute . ((string)$this->additionalParameters['mountPoint'] !== '' ? ' (MountPoint ' . $this->additionalParameters['mountPoint'] . ')' : '') . ((int)($this->additionalParameters['sysLanguageUid']) > 0 ? ' (SysLanguageUid ' . $this->additionalParameters['sysLanguageUid'] . ')' : '') . ((string)($this->additionalParameters['additionalGetParameters'] ?? '') !== '' ? ' (AdditionalGetParameters ' . $this->additionalParameters['additionalGetParameters'] . ')' : '') . ((string)$this->additionalParameters['domainName'] !== '' ? ' (DomainName ' . $this->additionalParameters['domainName'] . ')' : '') . '</li>';
                    }
                    $this->dispatch(new BackendAfterRenderSingleHreflangAttributeEvent($this));
                }
                if (count($this->hreflangAttributes) > 0) {
                    $this->renderedListItem .= '<ul style="list-style:disc inside; margin-left: 20px;">' . implode($this->hreflangAttributes) . '</ul>';
                }
                $this->renderedListItem .= '</li>';
                $this->dispatch(new BackendAfterRenderSinglePageEvent($this));
                $this->renderedListItems[] = $this->renderedListItem;
            }
            sort($this->renderedListItems);
            $this->renderedList = '<ul>' . implode($this->renderedListItems) . '</ul>';
        }

        $this->dispatch(new BackendAfterRenderEvent($this));

        return $this->renderedList;
    }

    /**
     * Dispatches an event through the TYPO3 event dispatcher.
     */
    protected function dispatch(object $event): void
    {
        $eventDispatcher = GeneralUtility::makeInstance(EventDispatcher::class);
        $eventDispatcher->dispatch($event);
    }

    /**
     * Renders the hreflang link tags of the current frontend page (TypoScript userFunc).
     *
     * @param string $content Content rendered before the tags
     * @param array<string, mixed> $conf TypoScript configuration of the userFunc
     * @return string
     */
    public function renderFrontendList($content, $conf)
    {
        $this->renderedList = '';
        $this->renderedListItems = [];
        if ((int)$GLOBALS['TYPO3_REQUEST']->getAttribute('frontend.page.information')->getId() > 0) {
            $this->getParameters = $GLOBALS['TYPO3_REQUEST']->getQueryParams();

            $relations = $this->getCachedRelations($GLOBALS['TYPO3_REQUEST']->getAttribute('frontend.page.information')->getId());
            foreach ($relations as $this->relatedPage => $info) {
                foreach ($info as $this->hreflangAttribute => $this->additionalParameters) {
                    $this->renderedListItem = '';
                    $this->validRelation = true;
                    $this->getParameters = $GLOBALS['TYPO3_REQUEST']->getQueryParams();
                    unset($this->getParameters['id']);
                    $this->getParameters['L'] = (int)($this->additionalParameters['sysLanguageUid']);
                    unset($this->getParameters['MP']);
                    if ((string)$this->additionalParameters['mountPoint'] !== '') {
                        $this->getParameters['MP'] = $this->additionalParameters['mountPoint'];
                    }
                    if ((string)($this->additionalParameters['additionalGetParameters'] ?? '') !== '') {
                        $additionalParameters = [];
                        parse_str((string)$this->additionalParameters['additionalGetParameters'], $additionalParameters);
                        $this->getParameters = array_merge($this->getParameters, $additionalParameters);
                    }

                    $this->dispatch(new FrontendBeforeRenderSingleTagEvent($this));

                    if ($this->validRelation) {
                        $this->renderedListItem = '<link rel="alternate" hreflang="' . $this->hreflangAttribute . '" href="' . $this->buildLink() . '" />';
                    }
                    $this->dispatch(new FrontendAfterRenderSingleTagEvent($this));
                    if ($this->renderedListItem !== '') {
                        $this->renderedListItems[] = $this->renderedListItem;
                    }
                }
            }
            sort($this->renderedListItems);
            $this->renderedList = "\n" . implode("\n", $this->renderedListItems) . "\n";
        }

        $this->renderedList = $content . $this->renderedList;

        $this->dispatch(new FrontendAfterRenderEvent($this));

        return $this->renderedList;
    }

    /**
     * Sets the GET parameters used to build the link of the current tag.
     *
     * @param array<string, mixed> $getParameters
     */
    public function setGetParameters($getParameters): void
    {
        $this->getParameters = $getParameters;
    }

    /**
     * Returns the GET parameters used to build the link of the current tag.
     *
     * @return array<string, mixed>
     */
    public function getGetParameters()
    {
        return $this->getParameters;
    }

    /**
     * Sets the uid of the page currently rendered.
     */
    public function setRelatedPage(int|string $relatedPage): void
    {
        $this->relatedPage = (int)$relatedPage;
    }

    /**
     * Returns the uid of the page currently rendered.
     */
    public function getRelatedPage()
    {
        return $this->relatedPage;
    }

    /**
     * Sets the hreflang attribute value currently rendered.
     */
    public function setHreflangAttribute($hreflangAttribute): void
    {
        $this->hreflangAttribute = (string)$hreflangAttribute;
    }

    /**
     * Returns the hreflang attribute value currently rendered.
     */
    public function getHreflangAttribute()
    {
        return $this->hreflangAttribute;
    }

    /**
     * Sets the hreflang attributes of the page currently rendered.
     *
     * @param array<int|string, mixed> $hreflangAttributes
     */
    public function setHreflangAttributes($hreflangAttributes): void
    {
        $this->hreflangAttributes = $hreflangAttributes;
    }

    /**
     * Returns the hreflang attributes of the page currently rendered.
     *
     * @return array<int|string, mixed>
     */
    public function getHreflangAttributes()
    {
        return $this->hreflangAttributes;
    }

    /**
     * Sets the additional parameters (sysLanguageUid, mountPoint, domainName, additionalGetParameters) of the current attribute.
     *
     * @param array<string, mixed> $additionalParameters
     */
    public function setAdditionalParameters($additionalParameters): void
    {
        $this->additionalParameters = $additionalParameters;
    }

    /**
     * Returns the additional parameters of the current attribute.
     *
     * @return array<string, mixed>
     */
    public function getAdditionalParameters()
    {
        return $this->additionalParameters;
    }

    /**
     * Sets the markup of the list item currently rendered.
     */
    public function setRenderedListItem($renderedListItem): void
    {
        $this->renderedListItem = (string)$renderedListItem;
    }

    /**
     * Returns the markup of the list item currently rendered.
     */
    public function getRenderedListItem()
    {
        return $this->renderedListItem;
    }

    /**
     * Sets the markup of all list items rendered so far.
     *
     * @param array<int, string> $renderedListItems
     */
    public function setRenderedListItems($renderedListItems): void
    {
        $this->renderedListItems = $renderedListItems;
    }

    /**
     * Returns the markup of all list items rendered so far.
     *
     * @return array<int, string>
     */
    public function getRenderedListItems()
    {
        return $this->renderedListItems;
    }

    /**
     * Sets the markup of the complete rendered list.
     */
    public function setRenderedList($renderedList): void
    {
        $this->renderedList = (string)$renderedList;
    }

    /**
     * Returns the markup of the complete rendered list.
     */
    public function getRenderedList()
    {
        return $this->renderedList;
    }

    /**
     * Tells whether the current relation is rendered; event listeners can switch it off.
     */
    public function getValidRelation()
    {
        return $this->validRelation;
    }

    /**
     * Marks the current relation as valid (rendered) or invalid (skipped).
     */
    public function setValidRelation($validRelation): void
    {
        $this->validRelation = (bool)$validRelation;
    }

    /**
     * Returns the hreflang attributes of all pages related to $pageId, from cache or freshly built and then cached.
     *
     * @param int $pageId
     * @return array<int, array<int|string, mixed>> Hreflang attributes indexed by related page uid
     */
    public function getCachedRelations($pageId)
    {
        $relatedPages = [];
        RelatedPages::buildRelations($pageId, $relatedPages);
        sort($relatedPages);

        // get relations from cache
        $cacheIdentifier = implode('_', $relatedPages);
        $relationFromCache = $this->getCacheInstance()->get($cacheIdentifier);
        //Check, if the current page is already cached
        if (is_array($relationFromCache)) {
            $relations = $relationFromCache;
        } else {
            // If $relationsFromCache is empty array, it hasn't been cached. Calculate the value and store it in the cache:
            $relations = [];

            foreach ($relatedPages as $relatedPage) {
                $relations[$relatedPage] = $this->buildHreflangAttributes($relatedPage);
            }
            // prepend each related page (= array_keys($relations)) with "pageId_" and use this as tag. So this cache is
            // cleared, when the corresponding page cache is cleared
            // @see EXT:core/Classes/DataHandling/DataHandler.php::clear_cache()
            $tags = array_map(function ($value) {
                return 'pageId_' . $value;
            }, $relatedPages);
            if (!empty($tags) && $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['bgm_hreflang']['feature']['clearCacheInFrontent']) {
                $this->getCacheManager()->flushCachesInGroupByTags('pages', $tags);
            }
            $this->getCacheInstance()->set((string)$cacheIdentifier, $relations, $tags, 84000);
        }

        return $relations;
    }

    /**
     * Builds the hreflang attributes for the default language and all translations of $pageId.
     *
     * @param int $pageId
     * @param string $mountPoint
     * @return array<int|string, mixed>
     */
    protected function buildHreflangAttributes($pageId, $mountPoint = '')
    {
        $this->hreflangAttributes = [];

        try {
            /** @var RootlineUtility $rootlineUtility */
            $rootlineUtility = GeneralUtility::makeInstance(RootlineUtility::class, $pageId, $mountPoint);
            $rootline = $rootlineUtility->get();
        } catch (\Exception $exception) {
            $this->logger->error('Exception while getting rootline for page ' . (int)$pageId, [
                'Exception ' . $exception->getCode(),
                $exception->getMessage(),
            ]);
            return $this->hreflangAttributes;
        }
        $rootPageId = $this->getRootPageId($rootline);
        if ($rootPageId === 0) {
            $this->logger->error('Page ' . (int)$pageId . 'has no rootline!');
            return $this->hreflangAttributes;
        }

        $countryMapping = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['bgm_hreflang']['countryMapping'][(int)$rootPageId];
        $defaultCountryId = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['bgm_hreflang']['defaultCountryId'];
        $domainName = (!empty($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['bgm_hreflang']['countryMapping'][(int)$rootPageId]['domainName'])) ?
            $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['bgm_hreflang']['countryMapping'][(int)$rootPageId]['domainName'] : '';

        if ($rootPageId == $defaultCountryId || isset($countryMapping['languageMapping'][0])) {
            $this->hreflangAttributes[($rootPageId == $defaultCountryId ? 'x-default' : $countryMapping['languageMapping'][0] . '-' . $countryMapping['countryCode'])] = [
                'sysLanguageUid' => 0,
                'mountPoint' => $mountPoint,
                'domainName' => $domainName,
                'additionalGetParameters' => $countryMapping['additionalGetParameters'][0],
            ];
        }

        $queryBuilder = $this->connectionPool
            ->getQueryBuilderForTable('pages');
        $translations = $queryBuilder
            ->select('sys_language_uid')
            ->from('pages')
            ->where($queryBuilder->expr()->eq('l10n_parent', (int)$pageId))->andWhere($queryBuilder->expr()->gt('sys_language_uid', 0))->executeQuery()->fetchAllAssociative();
        foreach ($translations as $translation) {
            $sysLanguageUid = $translation['sys_language_uid'];
            if (isset($countryMapping['languageMapping'][$sysLanguageUid])) {
                $this->hreflangAttributes[$countryMapping['languageMapping'][$sysLanguageUid] . ($rootPageId == $defaultCountryId ? '' : '-' . $countryMapping['countryCode'])] = [
                    'sysLanguageUid' => $sysLanguageUid,
                    'mountPoint' => $mountPoint,
                    'domainName' => $domainName,
                    'additionalGetParameters' => $countryMapping['additionalGetParameters'][$sysLanguageUid],
                ];
            }
        }

        if ($countryMapping['additionalCountries']) {
            foreach ($countryMapping['additionalCountries'] as $additionalCountry) {
                if (isset($countryMapping['languageMapping'][0])) {
                    $this->hreflangAttributes[$countryMapping['languageMapping'][0] . '-' . $additionalCountry] = [
                        'sysLanguageUid' => 0,
                        'mountPoint' => $mountPoint,
                        'domainName' => $domainName,
                        'additionalGetParameters' => $countryMapping['additionalGetParameters'][0],
                    ];
                }
                foreach ($translations as $translation) {
                    $sysLanguageUid = $translation['sys_language_uid'];
                    if (isset($countryMapping['languageMapping'][$sysLanguageUid])) {
                        $this->hreflangAttributes[$countryMapping['languageMapping'][$sysLanguageUid] . '-' . $additionalCountry] = [
                            'sysLanguageUid' => $sysLanguageUid,
                            'mountPoint' => $mountPoint,
                            'domainName' => $domainName,
                            'additionalGetParameters' => $countryMapping['additionalGetParameters'][$sysLanguageUid],
                        ];
                    }
                }
            }
        }

        if (strlen($mountPoint) == 0) { //@TODO nested mountpoints are to expensive
            //check, if the current page is mounted somewhere
            $rootlineMountPoints = $this->getMountpoints($rootline);
            if (count($rootlineMountPoints) > 0) {
                foreach ($rootlineMountPoints as $rootlineMountPoint) {
                    $this->hreflangAttributes = array_merge($this->hreflangAttributes, $this->buildHreflangAttributes($pageId, $rootlineMountPoint['mountPoint']));
                }
            }
        }

        $this->dispatch(new BuildHreflangAttributesEvent($this));

        return $this->hreflangAttributes;
    }

    /**
     * Searches for pages in the $rootline which are mounted somewhere and returns their mount point parameters.
     *
     * @param array<int, array<string, mixed>> $rootline
     * @return array<int, array<string, mixed>>
     */
    protected function getMountPoints(array $rootline): array
    {
        $rootlineIds = [];
        foreach ($rootline as $page) {
            $rootlineIds[] = $page['uid'];
        }
        $queryBuilder = $this->connectionPool
            ->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $mountPoints = $queryBuilder
            ->selectLiteral('CONCAT(mount_pid, "-", uid) AS mountPoint')
            ->from('pages')
            ->where($queryBuilder->expr()->eq('doktype', 7))->andWhere($queryBuilder->expr()->in('mount_pid', $rootlineIds))->executeQuery()->fetchAllAssociative();

        return $mountPoints;
    }

    /**
     * Returns the uid of the closest page with is_siteroot=1 in the rootline, 0 if there is none.
     *
     * @param array<int, array<string, mixed>> $rootline
     */
    protected function getRootPageId(array $rootline): int
    {
        $rootPageId = 0;
        foreach ($rootline as $rootlinePage) {
            if ((int)($rootlinePage['is_siteroot']) == 1) {
                $rootPageId = (int)$rootlinePage['uid'];
                break;
            }
        }
        return $rootPageId;
    }

    /**
     * Builds the absolute URL of the related page, replacing parts of it by the configured domain name.
     */
    protected function buildLink(): string
    {
        $additionalParams = '';
        if (!empty($this->getParameters)) {
            $additionalParams = HttpUtility::buildQueryString($this->getParameters, '&');
        }
        $contentObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $link = $contentObject->typoLink_URL(
            [
                'parameter' => self::createTypolinkParameterFromArguments((string)$this->relatedPage, $additionalParams),
                'forceAbsoluteUrl' => true,
            ]
        );
        if ($this->additionalParameters['domainName']) {
            $linkParts = parse_url($link);
            $domainParts = parse_url($this->additionalParameters['domainName']);
            foreach (['scheme', 'host', 'port', 'user', 'pass'] as $part) {
                if ((string)($domainParts[$part] ?? '') !== '') {
                    $linkParts[$part] = $domainParts[$part];
                }
            }
            $link = $this->unparse_url($linkParts);
        }
        return $link;
    }

    /**
     * Transforms ViewHelper arguments to typo3link.parameters.typoscript option as array.
     *
     * @param string $parameter Example: 19 _blank - "testtitle with whitespace" &X=y
     *
     * @return string The final TypoLink string
     */
    protected static function createTypolinkParameterFromArguments(string $parameter, string $additionalParameters = ''): string
    {
        $typoLinkCodec = GeneralUtility::makeInstance(\TYPO3\CMS\Core\LinkHandling\TypoLinkCodecService::class);
        $typolinkConfiguration = $typoLinkCodec->decode($parameter);
        if ($additionalParameters) {
            $typolinkConfiguration['additionalParams'] .= $additionalParameters;
        }
        return $typoLinkCodec->encode($typolinkConfiguration);
    }

    /**
     * Assembles a URL from the parts returned by parse_url().
     *
     * @param array<string, mixed> $parsed_url result from parse_url
     */
    protected function unparse_url(array $parsed_url): string
    {
        $scheme = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
        $host = isset($parsed_url['host']) ? $parsed_url['host'] : '';
        $port = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
        $user = isset($parsed_url['user']) ? $parsed_url['user'] : '';
        $pass = isset($parsed_url['pass']) ? ':' . $parsed_url['pass'] : '';
        $pass = ($user || $pass) ? $pass . '@' : '';
        $path = isset($parsed_url['path']) ? $parsed_url['path'] : '';
        $query = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
        $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
        return $scheme . $user . $pass . $host . $port . $path . $query . $fragment;
    }

    /**
     * Returns the CacheManager instance.
     */
    protected function getCacheManager(): CacheManager
    {
        return GeneralUtility::makeInstance(CacheManager::class);
    }

    /**
     * Returns the cache frontend holding the hreflang relations.
     *
     * @throws \TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException
     */
    protected function getCacheInstance(): FrontendInterface
    {
        return $this->getCacheManager()->getCache('tx_bgmhreflang_cache');
    }
}
