<?php

if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

//Register cache
if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_bgmhreflang_cache'])) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_bgmhreflang_cache'] = array();
}
//Clear cache whene page cache is cleared
if (!isset($TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['tx_bgmhreflang_cache']['groups'])) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_bgmhreflang_cache']['groups'] = array('pages', 'all');
}

/**
 * DEMO CONFIGURATION
 */
/*
//"sys_language_uid" and "isolanguagecode" have to be unique in the array $languageMapping!
$languageMapping = array(
	//sys_language_uid => isolanguagecode,
	1 => 'de', //Deutsch
	2 => 'en', //Englisch
	3 => 'fr', //Französisch
);
//"pageid" is the rootpage of a country tree. It has to be unique in the array $countryMapping!
//"isocountrycode" has to be unique in the array $countryMapping!
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['bgm_hreflang']['countryMapping'] = array(
	pageid => array(
		'countryCode' => isocountrycode,
		//"$languageMapping + array(0 => isolanguagecode)" can be assigned more than once with the same isolanguagecode as languageMapping in the array countryMapping.
		'languageMapping' => $languageMapping + array(0 => isolanguagecode),
		//"additionalGetParameters" is optional
		'additionalGetParameters' => array(
			//"sys_language_uid" has to be unique in the array $additionalGetParameters!
			sys_language_uid => isolanguagecode,
		),
		//domainName is optional
		'domainName' => 'https://www.domain.tld',
		//"additionalCountries" is optional
		'additionalCountries' => array(isocountrycode2, isocountrycode3),
	),

	12 => array( //International
		'countryCode' => 'en',
		'languageMapping' => $languageMapping + array(0 => 'en'),
		'additionalGetParameters' => array(
			1 => '&foo=bar',
		),
		'domainName' => 'https://www.my-domain.com',
	),
	34 => array( //Deutschland
		'countryCode' => 'de',
		'languageMapping' => $languageMapping + array(0 => 'de'),
		'additionalCountries' => array('at', 'ch'),
		'domainName' => 'https://www.my-domain.de',
	),
	56 => array( //UK
		'countryCode' => 'gb',
		'languageMapping' => $languageMapping + array(0 => 'en'),
		'domainName' => 'https://www.my-domain.co.uk',
	),
	78 => array( //France
		'countryCode' => 'fr',
		'languageMapping' => $languageMapping + array(0 => 'fr'),
	),
);
//If L==0, pages in this tree are rendered with 'x-default', else only the isolanguagecode is used (without the isocountrycode)
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['bgm_hreflang']['defaultCountryId'] = 12;
*/
?>
