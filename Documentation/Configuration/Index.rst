.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _configuration:

Configuration
=============

The configuration is done in the AdditionalConfiguration.php or your Theme-Extension's ext_localconf.php:

.. code:: php

	$languageMapping = array(

		//"sys_language_uid" and "isolanguagecode" have to be unique in the array $languageMapping!
		sys_language_uid => isolanguagecode,

		//Example
		1 => 'de', //german
		11 => 'en', //english
		21 => 'it', //italian
		31 => 'fr', //french
	);

	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['bgm_hreflang']['countryMapping'] = array(

		//"pageid" is the rootpage of a country branch. It has to be unique in the array $countryMapping!
		pageid => array(

			//"isocountrycode" has to be unique in the array $countryMapping!
			'countryCode' => isocountrycode,

			//'$languageMapping + array(0 => isolanguagecode)' can be assigned more than once with the same "isolanguagecode" in the array countryMapping.
			'languageMapping' => $languageMapping + array(0 => isolanguagecode),

			//This optional 'additionalGetParameters' can be assigned to every language of the current country
			'additionalGetParameters' => array(
				//"sys_language_uid" has to be unique in the array $additionalGetParameters!
				sys_language_uid => additionalGetParameters,

				//Example
				1 => '&foo=bar&john=doe', //append &foo=bar&john=doe to the link to sys_language 1 in this country
			),

			//This optional 'domainName' can be assigned, if you would like to prepend a certain domain name before your urls.
			//It overrides an automatically assigned domain from the typolink function.
			'domainName' => 'https://www.domain.tld',

			//This is optional. You need this, if you want the Germany country branch to be used as Austrian country branch, too.
			'additionalCountries' => array(isocountrycode, isocountrycode, ...),
		),

		//Example
		61 => array( //International
			'countryCode' => 'en',
			'languageMapping' => $languageMapping + array(0 => 'en'),
			'additionalGetParameters' => array(
				1 => '&foo=bar&john=doe',
			),
			'domainName' => 'https://www.my-domain.com',
		),
		111 => array( //Germany and Austria
			'countryCode' => 'de',
			'languageMapping' => $languageMapping + array(0 => 'de'),
			'additionalGetParameters' => array(
				0 => '&foo=0',
				1 => '&foo=1',
				31 => '&foo=31',
			),
			'additionalCountries' => array('at'),
			'domainName' => 'https://www.my-domain.de',
		),
		161 => array( //Switzerland
			'countryCode' => 'ch',
			'languageMapping' => $languageMapping + array(0 => 'de'),
			'domainName' => 'https://www.my-domain.it',
		),
		211 => array( //Italy
			'countryCode' => 'it',
			'languageMapping' => $languageMapping + array(0 => 'it'),
		),
	);

	//If $_GET['L']==0, pages in this tree are rendered with hreflang="x-default", else only the isolanguagecode is used (without the isocountrycode)
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['bgm_hreflang']['defaultCountryId'] = 61;

	//Cache is cleared in frontend for all related pages if no cache entry for the current page has been found.
	//Here you can disable this behaviour, if you are sure the new DataHandler backend hook is enough cache clearing.
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['bgm_hreflang']['feature']['clearCacheInFrontent'] = true;

And you need some TypoScript:

.. code:: typoscript

	page.headerData.30 = USER
	page.headerData.30 {
		userFunc = BGM\BgmHreflang\Utility\HreflangTags->renderFrontendList
	}
