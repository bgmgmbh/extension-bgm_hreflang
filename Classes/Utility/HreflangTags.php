<?php
namespace BGM\BgmHreflang\Utility;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Service\TypoLinkCodecService;

class HreflangTags {

	/**
	 * @var \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface
	 */
	protected $cacheInstance;

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
	 * @var integer
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
	 * @var boolean
	 * @see renderBackendList(), renderFrontendList()
	 */
	protected $validRelation;

	public function __construct(){
		$this->signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher');
		$this->initializeCache();
	}

	/**
	 * Render the related pages and the shortest path to them
	 *
	 * @param int $uid
	 */
	public function renderBackendList($uid){
		$this->renderedList = '';
		$this->renderedListItems = array();
		if((int) $uid > 0) {
			$relations = $this->getCachedRelations((int) $uid);

			foreach($relations as $this->relatedPage => $info){
				$this->signalSlotDispatcher->dispatch(__CLASS__, 'backend_beforeRenderSinglePage', array($this));
				$this->renderedListItem = '<li>' . \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordPath($this->relatedPage, '', 1000) . ' [' . $this->relatedPage . ']';
				$this->hreflangAttributes = array();
				foreach ($info as $this->hreflangAttribute => $this->additionalParameters) {
					$this->validRelation = true;
					$this->signalSlotDispatcher->dispatch(__CLASS__, 'backend_beforeRenderSingleHreflangAttribute', array($this));
					if ($this->validRelation) {
						$this->hreflangAttributes[] = '<li>' . $this->hreflangAttribute . (strlen($this->additionalParameters['mountPoint']) > 0 ? ' (MountPoint ' . $this->additionalParameters['mountPoint'] . ')' : '') . (intval($this->additionalParameters['sysLanguageUid']) > 0 ? ' (SysLanguageUid ' . $this->additionalParameters['sysLanguageUid'] . ')' : '') . (strlen($this->additionalParameters['additionalGetParameters']) > 0 ? ' (AdditionalGetParameters ' . $this->additionalParameters['additionalGetParameters'] . ')' : '') . (strlen($this->additionalParameters['domainName']) > 0 ? ' (DomainName ' . $this->additionalParameters['domainName'] . ')' : '') . '</li>';
					}
					$this->signalSlotDispatcher->dispatch(__CLASS__, 'backend_afterRenderSingleHreflangAttribute', array($this));
				}
				if (count($this->hreflangAttributes) > 0) {
					$this->renderedListItem .= '<ul style="list-style:disc inside; margin-left: 20px;">' . implode($this->hreflangAttributes) . '</ul>';
				}
				$this->renderedListItem .= '</li>';
				$this->signalSlotDispatcher->dispatch(__CLASS__, 'backend_afterRenderSinglePage', array($this));
				$this->renderedListItems[] = $this->renderedListItem;
			}
			sort($this->renderedListItems);
			$this->renderedList = '<ul>' . implode($this->renderedListItems) . '</ul>';
		}

		$this->signalSlotDispatcher->dispatch(__CLASS__, 'backend_afterRender', array($this));

		return $this->renderedList;
	}

	/**
	 * Renders the hreflang-tags
	 *
	 * @param string $content
	 * @param array $conf
	 * @return string
	 */
	public function renderFrontendList($content, $conf){
		$this->renderedList = '';
		$this->renderedListItems = array();
		if (intval($GLOBALS['TSFE']->id) > 0) {
			$this->getParameters = \TYPO3\CMS\Core\Utility\GeneralUtility::_GET();

			$relations = $this->getCachedRelations($GLOBALS['TSFE']->id);

			foreach ($relations as $this->relatedPage => $info) {
				foreach ($info as $this->hreflangAttribute => $this->additionalParameters) {
					$this->renderedListItem = '';
					$this->validRelation = true;
					$this->getParameters = \TYPO3\CMS\Core\Utility\GeneralUtility::_GET();
					unset($this->getParameters['id']);
					$this->getParameters['L'] = intval($this->additionalParameters['sysLanguageUid']);
					unset($this->getParameters['MP']);
					if(strlen($this->additionalParameters['mountPoint']) > 0){
						$this->getParameters['MP'] = $this->additionalParameters['mountPoint'];
					}
					if(strlen($this->additionalParameters['additionalGetParameters']) > 0){
						$this->getParameters = array_merge($this->getParameters, \TYPO3\CMS\Core\Utility\GeneralUtility::explodeUrl2Array($this->additionalParameters['additionalGetParameters'], true));
					}

					$this->signalSlotDispatcher->dispatch(__CLASS__, 'frontend_beforeRenderSingleTag', array($this));
					if ($this->validRelation) {
						$this->renderedListItem = '<link rel="alternate" hreflang="' . $this->hreflangAttribute . '" href="' . $this->buildLink() . '" />';
					}
					$this->signalSlotDispatcher->dispatch(__CLASS__, 'frontend_afterRenderSingleTag', array($this));
					if($this->renderedListItem !== ''){
						$this->renderedListItems[] = $this->renderedListItem;
					}
				}
			}
			sort($this->renderedListItems);
			$this->renderedList = "\n" . implode($this->renderedListItems, "\n") . "\n";
		}

		$this->renderedList = $content . $this->renderedList;

		$this->signalSlotDispatcher->dispatch(__CLASS__, 'frontend_afterRender', array($this));

		return $this->renderedList;
	}

	/**
	 * @param array $getParameters
	 */
	public function setGetParameters($getParameters){
		$this->getParameters = $getParameters;
	}

	/**
	 * @return array
	 */
	public function getGetParameters() {
		return $this->getParameters;
	}

	/**
	 * @param integer $relatedPage
	 */
	public function setRelatedPage($relatedPage){
		$this->relatedPage = $relatedPage;
	}

	/**
	 * @return int
	 */
	public function getRelatedPage() {
		return $this->relatedPage;
	}

	/**
	 * @param string $hreflangAttribute
	 */
	public function setHreflangAttribute($hreflangAttribute){
		$this->hreflangAttribute = $hreflangAttribute;
	}

	/**
	 * @return string
	 */
	public function getHreflangAttribute() {
		return $this->hreflangAttribute;
	}

	/**
	 * @param array $hreflangAttributes
	 */
	public function setHreflangAttributes($hreflangAttributes){
		$this->hreflangAttributes = $hreflangAttributes;
	}

	/**
	 * @return array
	 */
	public function getHreflangAttributes() {
		return $this->hreflangAttributes;
	}

	/**
	 * @param string $additionalParameters
	 */
	public function setAdditionalParameters($additionalParameters){
		$this->additionalParameters = $additionalParameters;
	}

	/**
	 * @return string
	 */
	public function getAdditionalParameters() {
		return $this->additionalParameters;
	}

	/**
	 * @param string $renderedListItem
	 */
	public function setRenderedListItem($renderedListItem){
		$this->renderedListItem = $renderedListItem;
	}

	/**
	 * @return string
	 */
	public function getRenderedListItem() {
		return $this->renderedListItem;
	}

	/**
	 * @param array $renderedListItems
	 */
	public function setRenderedListItems($renderedListItems){
		$this->renderedListItems = $renderedListItems;
	}

	/**
	 * @return array
	 */
	public function getRenderedListItems() {
		return $this->renderedListItems;
	}

	/**
	 * @param string $renderedList
	 */
	public function setRenderedList($renderedList){
		$this->renderedList = $renderedList;
	}

	/**
	 * @return string
	 */
	public function getRenderedList() {
		return $this->renderedList;
	}

	/**
	 * @return boolean
	 */
	public function getValidRelation() {
		return $this->validRelation;
	}

	/**
	 * @param boolean $validRelation
	 */
	public function setValidRelation($validRelation) {
		$this->validRelation = $validRelation;
	}


	/**
	 * Get hreflang relations from cache or generate the list and cache them
	 *
	 * @param integer $pageId
	 * @return array $relations
	 */
	public function getCachedRelations($pageId){
		// get relations from cache
		$cacheIdentifier = $pageId;
		$cacheTag = 'pageId_' . $pageId;
		$relationsFromCache = $this->cacheInstance->getByTag($cacheTag);
		//Check, if the current page is already cached
		if(count($relationsFromCache)>0 && is_array($relationsFromCache[0][$cacheIdentifier])){
			$relations = $relationsFromCache[0];
		} else {
		// If $relationsFromCache is empty array, it hasn't been cached. Calculate the value and store it in the cache:
			$relations = array();
			$this->buildRelations($pageId, $relations);
			// prepend each related page (= array_keys($relations)) with "pageId_" and use this as tag. So this cache is
			// cleared, when the corresponding page cache is cleared
			// @see EXT:core/Classes/DataHandling/DataHandler.php::clear_cache()
			$tags = array_map(function ($value) {
				return 'pageId_' . $value;
			}, array_keys($relations));
			foreach($tags as $tag){
				$this->cacheInstance->flushByTag($tag);
			}
			$this->cacheInstance->set((string) $cacheIdentifier, $relations, $tags, 84000);
		}

		return $relations;
	}

	/**
	 * Get hreflang relations recursivly
	 *
	 * @param integer $pageId
	 * @param array $relations
	 */
	protected function buildRelations($pageId, &$relations) {
		$relations[$pageId] = $this->buildHreflangAttributes($pageId);
		$queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
			->getQueryBuilderForTable('tx_bgmhreflang_page_page_mm');

		$directRelations = $queryBuilder
			->select('*')
			->from('tx_bgmhreflang_page_page_mm')
			->where($queryBuilder->expr()->eq('uid_local', intval($pageId)))
			->execute()
			->fetchAll();
		for ($i = 0; $i < count($directRelations); $i++) {
			if (!isset($relations[$directRelations[$i]['uid_foreign']])) {
				$this->buildRelations($directRelations[$i]['uid_foreign'], $relations);
			}
		}

		$indirectRelations = $queryBuilder
			->select('*')
			->from('tx_bgmhreflang_page_page_mm')
			->where($queryBuilder->expr()->eq('uid_foreign', intval($pageId)))
			->execute()
			->fetchAll();
		for ($i = 0; $i < count($indirectRelations); $i++) {
			if(!isset($relations[$indirectRelations[$i]['uid_local']])){
				$this->buildRelations($indirectRelations[$i]['uid_local'], $relations);
			}
		}
	}

	/**
	 * Get the hreflangattributes for the default language and all translations of $pageId
	 *
	 * @param integer $pageId
	 * @param string $mountPoint
	 * @return array $this->hreflangAttributes
	 */
	protected function buildHreflangAttributes($pageId, $mountPoint='') {
		$this->hreflangAttributes = array();

		/** @var RootlineUtility $rootlineUtility */
		$rootlineUtility = GeneralUtility::makeInstance(RootlineUtility::class, $pageId, $mountPoint);
		$rootline = $rootlineUtility->get();
		$rootPageId = $this->getRootPageId($rootline);

		$countryMapping = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['bgm_hreflang']['countryMapping'][intval($rootPageId)];
		$defaultCountryId = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['bgm_hreflang']['defaultCountryId'];
		$domainName = (!empty($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['bgm_hreflang']['countryMapping'][intval($rootPageId)]['domainName']))?
			$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['bgm_hreflang']['countryMapping'][intval($rootPageId)]['domainName'] :'';

		if($rootPageId == $defaultCountryId || isset($countryMapping['languageMapping'][0])) {
			$this->hreflangAttributes[($rootPageId == $defaultCountryId ? 'x-default' : $countryMapping['languageMapping'][0] . '-' . $countryMapping['countryCode'])] = array(
				'sysLanguageUid' => 0,
				'mountPoint' => $mountPoint,
				'domainName' => $domainName,
				'additionalGetParameters' => $countryMapping['additionalGetParameters'][0],
			);
		}

		$queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
			->getQueryBuilderForTable('pages');
		$translations = $queryBuilder
			->select('sys_language_uid')
			->from('pages')
			->where($queryBuilder->expr()->eq('l10n_parent', intval($pageId)))
			->andWhere($queryBuilder->expr()->gt('sys_language_uid', 0))
			->execute()
			->fetchAll();
		foreach ($translations as $translation) {
			if($translation['sys_language_uid']){
				$translation = $translation['sys_language_uid'];
			}
			if(isset($countryMapping['languageMapping'][$translation])) {
				$this->hreflangAttributes[$countryMapping['languageMapping'][$translation] . ($rootPageId == $defaultCountryId ? '' : '-' . $countryMapping['countryCode'])] = array(
					'sysLanguageUid' => $translation,
					'mountPoint' => $mountPoint,
					'domainName' => $domainName,
					'additionalGetParameters' => $countryMapping['additionalGetParameters'][$translation],
				);
			}
		}

		if($countryMapping['additionalCountries']){
			foreach($countryMapping['additionalCountries'] as $additionalCountry){
				if (isset($countryMapping['languageMapping'][0])) {
					$this->hreflangAttributes[$countryMapping['languageMapping'][0] . '-' . $additionalCountry] = array(
						'sysLanguageUid' => 0,
						'mountPoint' => $mountPoint,
						'domainName' => $domainName,
						'additionalGetParameters' => $countryMapping['additionalGetParameters'][0],
					);
				}
				foreach ($translations as $translation) {
					if (isset($countryMapping['languageMapping'][$translation])) {
						$this->hreflangAttributes[$countryMapping['languageMapping'][$translation] . '-' . $additionalCountry] = array(
							'sysLanguageUid' => $translation,
							'mountPoint' => $mountPoint,
							'domainName' => $domainName,
							'additionalGetParameters' => $countryMapping['additionalGetParameters'][$translation],
						);
					}
				}
			}
		}

		if(strlen($mountPoint) == 0){ //@TODO nested mountpoints are to expensive
			//check, if the current page is mounted somewhere
			$mountPoints = $this->getMountpoints($rootline);
			if(count($mountPoints) > 0){
				foreach($mountPoints as $mountPoint){
					$this->hreflangAttributes = array_merge($this->hreflangAttributes, $this->buildHreflangAttributes($pageId, $mountPoint['mountPoint']));
				}
			}
		}

		$this->signalSlotDispatcher->dispatch(__CLASS__, 'buildHreflangAttributes', array($this));

		return $this->hreflangAttributes;
	}

	/**
	 * Search for pages in the $rootline, which are mounted somewhere and return an array with mpvars
	 *
	 * @param array $rootline
	 * @return mixed
	 */
	protected function getMountPoints($rootline){
		$rootlineIds = array();
		foreach($rootline as $page){
			$rootlineIds[] = $page['uid'];
		}
		$queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
			->getQueryBuilderForTable('pages');
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
	protected function getRootPageId($rootline){
		$rootPageId = 0;
		foreach ($rootline as $rootlinePage) {
			if (intval($rootlinePage['is_siteroot']) == 1) {
				$rootPageId = $rootlinePage['uid'];
				break;
			}
		}
		return $rootPageId;
	}

	/**
	 * Initialize cache instance to be ready to use
	 *
	 * @return void
	 */
	protected function initializeCache() {
		$this->cacheInstance = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Cache\\CacheManager')->getCache('tx_bgmhreflang_cache');
	}

	/**
	 * @return string
	 */
	protected function buildLink() {
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
	protected function unparse_url($parsed_url) {
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
}

?>
