<?php

namespace BGM\BgmHreflang\Utility;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Service\TypoLinkCodecService;

class HreflangTags implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher
     */
    protected $signalSlotDispatcher;

    /**
     * current $_GET parameters
     *
     * @var array
     * @see renderBackendList(), renderFrontendList()
     */
    protected $getParameters;

    /**
     * current related page
     *
     * @var int
     * @see renderBackendList(), renderFrontendList()
     */
    protected $relatedPage;

    /**
     * current hreflang attribute for the related page
     *
     * @var string
     * @see renderBackendList(), renderFrontendList()
     */
    protected $hreflangAttribute;

    /**
     * curent hreflang attributes for the related page
     *
     * @var array
     * @see renderBackendList(), renderFrontendList()
     */
    protected $hreflangAttributes;

    /**
     * additional parameters for the current hreflang attribute $hreflangAttribute.
     * contains the keys sysLanguageUid and mountPoint
     *
     * @var array
     * @see renderBackendList(), renderFrontendList()
     */
    protected $additionalParameters;

    /**
     * rendered item
     *
     * @var string
     * @see renderBackendList(), renderFrontendList()
     */
    protected $renderedListItem;

    /**
     * rendered items
     *
     * @var array
     * @see renderBackendList(), renderFrontendList()
     */
    protected $renderedListItems;

    /**
     * rendered list
     *
     * @var string
     * @see renderBackendList(), renderFrontendList()
     */
    protected $renderedList;

    /**
     * valid relation
     *
     * @var bool
     * @see renderBackendList(), renderFrontendList()
     */
    protected $validRelation;

    public function __construct()
    {
        $this->signalSlotDispatcher = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher');
    }

    /**
     * Render the related pages and the shortest path to them
     *
     * @param int $uid
     */
    public function renderBackendList($uid)
    {
        $this->renderedList = '';
        $this->renderedListItems = [];
        if ((int)$uid > 0) {
            $relations = $this->getCachedRelations((int)$uid);

            foreach ($relations as $this->relatedPage => $info) {
                $this->signalSlotDispatcher->dispatch(__CLASS__, 'backend_beforeRenderSinglePage', [$this]);
                $this->renderedListItem = '<li>' . BackendUtility::getRecordPath($this->relatedPage, '', 1000) . ' [' . $this->relatedPage . ']';
                $this->hreflangAttributes = [];
                foreach ($info as $this->hreflangAttribute => $this->additionalParameters) {
                    $this->validRelation = true;
                    $this->signalSlotDispatcher->dispatch(__CLASS__, 'backend_beforeRenderSingleHreflangAttribute', [$this]);
                    if ($this->validRelation) {
                        $this->hreflangAttributes[] = '<li>' . $this->hreflangAttribute . (strlen($this->additionalParameters['mountPoint']) > 0 ? ' (MountPoint ' . $this->additionalParameters['mountPoint'] . ')' : '') . ((int)($this->additionalParameters['sysLanguageUid']) > 0 ? ' (SysLanguageUid ' . $this->additionalParameters['sysLanguageUid'] . ')' : '') . (strlen($this->additionalParameters['additionalGetParameters']) > 0 ? ' (AdditionalGetParameters ' . $this->additionalParameters['additionalGetParameters'] . ')' : '') . (strlen($this->additionalParameters['domainName']) > 0 ? ' (DomainName ' . $this->additionalParameters['domainName'] . ')' : '') . '</li>';
                    }
                    $this->signalSlotDispatcher->dispatch(__CLASS__, 'backend_afterRenderSingleHreflangAttribute', [$this]);
                }
                if (count($this->hreflangAttributes) > 0) {
                    $this->renderedListItem .= '<ul style="list-style:disc inside; margin-left: 20px;">' . implode($this->hreflangAttributes) . '</ul>';
                }
                $this->renderedListItem .= '</li>';
                $this->signalSlotDispatcher->dispatch(__CLASS__, 'backend_afterRenderSinglePage', [$this]);
                $this->renderedListItems[] = $this->renderedListItem;
            }
            sort($this->renderedListItems);
            $this->renderedList = '<ul>' . implode($this->renderedListItems) . '</ul>';
        }

        $this->signalSlotDispatcher->dispatch(__CLASS__, 'backend_afterRender', [$this]);

        return $this->renderedList;
    }

    /**
     * Renders the hreflang-tags
     *
     * @param string $content
     * @param array $conf
     * @return string
     */
    public function renderFrontendList($content, $conf)
    {
        $this->renderedList = '';
        $this->renderedListItems = [];
        if ((int)($GLOBALS['TSFE']->id) > 0) {
            $this->getParameters = GeneralUtility::_GET();

            $relations = $this->getCachedRelations($GLOBALS['TSFE']->id);

            foreach ($relations as $this->relatedPage => $info) {
                foreach ($info as $this->hreflangAttribute => $this->additionalParameters) {
                    $this->renderedListItem = '';
                    $this->validRelation = true;
                    $this->getParameters = GeneralUtility::_GET();
                    unset($this->getParameters['id']);
                    $this->getParameters['L'] = (int)($this->additionalParameters['sysLanguageUid']);
                    unset($this->getParameters['MP']);
                    if (strlen($this->additionalParameters['mountPoint']) > 0) {
                        $this->getParameters['MP'] = $this->additionalParameters['mountPoint'];
                    }
                    if (strlen($this->additionalParameters['additionalGetParameters']) > 0) {
                        $additionalParameters = [];
                        parse_str($this->additionalParameters['additionalGetParameters']??'', $additionalParameters);
                        $this->getParameters = array_merge($this->getParameters, $additionalParameters);
                    }

                    $this->signalSlotDispatcher->dispatch(__CLASS__, 'frontend_beforeRenderSingleTag', [$this]);
                    if ($this->validRelation) {
                        $this->renderedListItem = '<link rel="alternate" hreflang="' . $this->hreflangAttribute . '" href="' . $this->buildLink() . '" />';
                    }
                    $this->signalSlotDispatcher->dispatch(__CLASS__, 'frontend_afterRenderSingleTag', [$this]);
                    if ($this->renderedListItem !== '') {
                        $this->renderedListItems[] = $this->renderedListItem;
                    }
                }
            }
            sort($this->renderedListItems);
            $this->renderedList = "\n" . implode("\n", $this->renderedListItems) . "\n";
        }

        $this->renderedList = $content . $this->renderedList;

        $this->signalSlotDispatcher->dispatch(__CLASS__, 'frontend_afterRender', [$this]);

        return $this->renderedList;
    }

    /**
     * @param array $getParameters
     */
    public function setGetParameters($getParameters)
    {
        $this->getParameters = $getParameters;
    }

    /**
     * @return array
     */
    public function getGetParameters()
    {
        return $this->getParameters;
    }

    /**
     * @param int $relatedPage
     */
    public function setRelatedPage($relatedPage)
    {
        $this->relatedPage = $relatedPage;
    }

    /**
     * @return int
     */
    public function getRelatedPage()
    {
        return $this->relatedPage;
    }

    /**
     * @param string $hreflangAttribute
     */
    public function setHreflangAttribute($hreflangAttribute)
    {
        $this->hreflangAttribute = $hreflangAttribute;
    }

    /**
     * @return string
     */
    public function getHreflangAttribute()
    {
        return $this->hreflangAttribute;
    }

    /**
     * @param array $hreflangAttributes
     */
    public function setHreflangAttributes($hreflangAttributes)
    {
        $this->hreflangAttributes = $hreflangAttributes;
    }

    /**
     * @return array
     */
    public function getHreflangAttributes()
    {
        return $this->hreflangAttributes;
    }

    /**
     * @param array $additionalParameters
     */
    public function setAdditionalParameters($additionalParameters)
    {
        $this->additionalParameters = $additionalParameters;
    }

    /**
     * @return array
     */
    public function getAdditionalParameters()
    {
        return $this->additionalParameters;
    }

    /**
     * @param string $renderedListItem
     */
    public function setRenderedListItem($renderedListItem)
    {
        $this->renderedListItem = $renderedListItem;
    }

    /**
     * @return string
     */
    public function getRenderedListItem()
    {
        return $this->renderedListItem;
    }

    /**
     * @param array $renderedListItems
     */
    public function setRenderedListItems($renderedListItems)
    {
        $this->renderedListItems = $renderedListItems;
    }

    /**
     * @return array
     */
    public function getRenderedListItems()
    {
        return $this->renderedListItems;
    }

    /**
     * @param string $renderedList
     */
    public function setRenderedList($renderedList)
    {
        $this->renderedList = $renderedList;
    }

    /**
     * @return string
     */
    public function getRenderedList()
    {
        return $this->renderedList;
    }

    /**
     * @return bool
     */
    public function getValidRelation()
    {
        return $this->validRelation;
    }

    /**
     * @param bool $validRelation
     */
    public function setValidRelation($validRelation)
    {
        $this->validRelation = $validRelation;
    }

    /**
     * Get hreflang relations from cache or generate the list and cache them
     *
     * @param int $pageId
     * @return array $relations
     */
    public function getCachedRelations($pageId)
    {
        // get relations from cache
        $cacheIdentifier = $pageId;
        $relationFromCache = $this->getCacheInstance()->get($cacheIdentifier);
        //Check, if the current page is already cached
        if (is_array($relationFromCache)) {
            $relations = $relationFromCache;
        } else {
            // If $relationsFromCache is empty array, it hasn't been cached. Calculate the value and store it in the cache:
            $relations = [];
            $this->buildRelations($pageId, $relations);
            // prepend each related page (= array_keys($relations)) with "pageId_" and use this as tag. So this cache is
            // cleared, when the corresponding page cache is cleared
            // @see EXT:core/Classes/DataHandling/DataHandler.php::clear_cache()
            $tags = array_map(function ($value) {
                return 'pageId_' . $value;
            }, array_keys($relations));
            if (!empty($tags)) {
                $this->getCacheManager()->flushCachesInGroupByTags('pages', $tags);
            }
            $this->getCacheInstance()->set((string)$cacheIdentifier, $relations, $tags, 84000);
        }

        return $relations;
    }

    /**
     * Get hreflang relations recursivly
     *
     * @param int $pageId
     * @param array $relations
     */
    protected function buildRelations($pageId, &$relations)
    {
        $relations[$pageId] = $this->buildHreflangAttributes($pageId);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $directRelations = $queryBuilder
            ->select('mm.*')
            ->from('tx_bgmhreflang_page_page_mm', 'mm')
            ->leftJoin('mm', 'pages', 'p', 'mm.uid_foreign = p.uid')
            ->where($queryBuilder->expr()->eq('mm.uid_local', (int)$pageId))
            ->execute()
            ->fetchAll();
        foreach ($directRelations as $directRelation) {
            if (!isset($relations[$directRelation['uid_foreign']])) {
                $this->buildRelations($directRelation['uid_foreign'], $relations);
            }
        }

        /** @var QueryBuilder $queryBuilder2 */
        $queryBuilder2 = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        $queryBuilder2->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $indirectRelations = $queryBuilder2
            ->select('mm.*')
            ->from('tx_bgmhreflang_page_page_mm', 'mm')
            ->leftJoin('mm', 'pages', 'p', 'mm.uid_local = p.uid')
            ->where($queryBuilder2->expr()->eq('mm.uid_foreign', (int)$pageId))
            ->execute()
            ->fetchAll();
        foreach ($indirectRelations as $indirectRelation) {
            if (!isset($relations[$indirectRelation['uid_local']])) {
                $this->buildRelations($indirectRelation['uid_local'], $relations);
            }
        }
    }

    /**
     * Get the hreflangattributes for the default language and all translations of $pageId
     *
     * @param int $pageId
     * @param string $mountPoint
     * @return array $this->hreflangAttributes
     */
    protected function buildHreflangAttributes($pageId, $mountPoint='')
    {
        $this->hreflangAttributes = [];

        try {
            /** @var RootlineUtility $rootlineUtility */
            $rootlineUtility = GeneralUtility::makeInstance(RootlineUtility::class, $pageId, $mountPoint);
            $rootline = $rootlineUtility->get();
        } catch (\Exception $exception) {
            $this->logger->error('Exception while getting rootline for page ' . (int)$pageId, [
                'Exception ' . $exception->getCode(),
                $exception->getMessage()
            ]);
            return $this->hreflangAttributes;
        }
        $rootPageId = $this->getRootPageId($rootline);
        if($rootPageId === 0){
            $this->logger->error('Page ' . (int)$pageId . 'has no rootline!');
            return $this->hreflangAttributes;
        }

        $countryMapping = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['bgm_hreflang']['countryMapping'][(int)$rootPageId];
        $defaultCountryId = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['bgm_hreflang']['defaultCountryId'];
        $domainName = (!empty($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['bgm_hreflang']['countryMapping'][(int)$rootPageId]['domainName']))?
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['bgm_hreflang']['countryMapping'][(int)$rootPageId]['domainName'] :'';

        if ($rootPageId == $defaultCountryId || isset($countryMapping['languageMapping'][0])) {
            $this->hreflangAttributes[($rootPageId == $defaultCountryId ? 'x-default' : $countryMapping['languageMapping'][0] . '-' . $countryMapping['countryCode'])] = [
                'sysLanguageUid' => 0,
                'mountPoint' => $mountPoint,
                'domainName' => $domainName,
                'additionalGetParameters' => $countryMapping['additionalGetParameters'][0],
            ];
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        $translations = $queryBuilder
            ->select('sys_language_uid')
            ->from('pages')
            ->where($queryBuilder->expr()->eq('l10n_parent', (int)$pageId))
            ->andWhere($queryBuilder->expr()->gt('sys_language_uid', 0))
            ->execute()
            ->fetchAll();
        foreach ($translations as $translation) {
            if ($translation['sys_language_uid']) {
                $translation = $translation['sys_language_uid'];
            }
            if (isset($countryMapping['languageMapping'][$translation])) {
                $this->hreflangAttributes[$countryMapping['languageMapping'][$translation] . ($rootPageId == $defaultCountryId ? '' : '-' . $countryMapping['countryCode'])] = [
                    'sysLanguageUid' => $translation,
                    'mountPoint' => $mountPoint,
                    'domainName' => $domainName,
                    'additionalGetParameters' => $countryMapping['additionalGetParameters'][$translation],
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
                    if (isset($countryMapping['languageMapping'][$translation])) {
                        $this->hreflangAttributes[$countryMapping['languageMapping'][$translation] . '-' . $additionalCountry] = [
                            'sysLanguageUid' => $translation,
                            'mountPoint' => $mountPoint,
                            'domainName' => $domainName,
                            'additionalGetParameters' => $countryMapping['additionalGetParameters'][$translation],
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

        $this->signalSlotDispatcher->dispatch(__CLASS__, 'buildHreflangAttributes', [$this]);

        return $this->hreflangAttributes;
    }

    /**
     * Search for pages in the $rootline, which are mounted somewhere and return an array with mpvars
     *
     * @param array $rootline
     * @return mixed
     */
    protected function getMountPoints($rootline)
    {
        $rootlineIds = [];
        foreach ($rootline as $page) {
            $rootlineIds[] = $page['uid'];
        }
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $mountPoints = $queryBuilder
            ->selectLiteral('CONCAT(mount_pid, "-", uid) AS mountPoint')
            ->from('pages')
            ->where($queryBuilder->expr()->eq('doktype', 7))
            ->andWhere($queryBuilder->expr()->in('mount_pid', $rootlineIds))
            ->execute()
            ->fetchAll();

        return $mountPoints;
    }

    /**
     * Search for the closest page with is_siteroot=1 in the rootline
     *
     * @param array $rootline
     * @return int
     */
    protected function getRootPageId($rootline)
    {
        $rootPageId = 0;
        foreach ($rootline as $rootlinePage) {
            if ((int)($rootlinePage['is_siteroot']) == 1) {
                $rootPageId = $rootlinePage['uid'];
                break;
            }
        }
        return $rootPageId;
    }

    /**
     * @return string
     */
    protected function buildLink()
    {
        if (is_array($this->getParameters)) {
            if (!empty($this->getParameters)) {
                $additionalParams = HttpUtility::buildQueryString($this->getParameters, '&');
            }
        } else {
            $additionalParams = $this->getParameters;
        }
        $contentObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $link = $contentObject->typoLink_URL(
            [
                'parameter' => self::createTypolinkParameterFromArguments($this->relatedPage, $additionalParams),
                'forceAbsoluteUrl' => true
            ]
        );
        if ($this->additionalParameters['domainName']) {
            $linkParts = parse_url($link);
            $domainParts = parse_url($this->additionalParameters['domainName']);
            $linkParts['scheme'] = strlen($domainParts['scheme']) > 0 ? $domainParts['scheme'] : $linkParts['scheme'];
            $linkParts['host'] = strlen($domainParts['host']) > 0 ? $domainParts['host'] : $linkParts['host'];
            $linkParts['port'] = strlen($domainParts['port']) > 0 ? $domainParts['port'] : $linkParts['port'];
            $linkParts['user'] = strlen($domainParts['user']) > 0 ? $domainParts['user'] : $linkParts['user'];
            $linkParts['pass'] = strlen($domainParts['pass']) > 0 ? $domainParts['pass'] : $linkParts['pass'];
            $link = $this->unparse_url($linkParts);
        }
        return $link;
    }

    /**
     * Transforms ViewHelper arguments to typo3link.parameters.typoscript option as array.
     *
     * @param string $parameter Example: 19 _blank - "testtitle with whitespace" &X=y
     * @param string $additionalParameters
     *
     * @return string The final TypoLink string
     */
    protected static function createTypolinkParameterFromArguments($parameter, $additionalParameters = '')
    {
        $typoLinkCodec = GeneralUtility::makeInstance(TypoLinkCodecService::class);
        $typolinkConfiguration = $typoLinkCodec->decode($parameter);
        if ($additionalParameters) {
            $typolinkConfiguration['additionalParams'] .= $additionalParameters;
        }
        return $typoLinkCodec->encode($typolinkConfiguration);
    }

    /**
     * @param array $parsed_url result from parse_url
     * @return string
     */
    protected function unparse_url($parsed_url)
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
     * Create and returns an instance of the CacheManager
     *
     * @return object|\Psr\Log\LoggerAwareInterface|CacheManager|\TYPO3\CMS\Core\SingletonInterface
     */
    protected function getCacheManager()
    {
        return GeneralUtility::makeInstance(CacheManager::class);
    }

    /**
     * @return FrontendInterface
     * @throws \TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException
     */
    protected function getCacheInstance()
    {
        return $this->getCacheManager()->getCache('tx_bgmhreflang_cache');
    }
}
