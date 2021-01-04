.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _developers:

Developers
==========

Fixtures from functional tests
_______________________________________

To import the database fixtures to your ddev database you can mount the xml files to your ddev db container and then
import them:

.. code:: bash

    echo "TRUNCATE TABLE pages; LOAD XML LOCAL INFILE '/var/www/html/packages/bgm-hreflang/Tests/Functional/Fixtures/Database/pages.xml' INTO TABLE pages ROWS IDENTIFIED BY '<pages>';" | ddev import-db --no-drop
    echo "TRUNCATE TABLE sys_language; LOAD XML LOCAL INFILE '/var/www/html/packages/bgm-hreflang/Tests/Functional/Fixtures/Database/sys_language.xml' INTO TABLE sys_language ROWS IDENTIFIED BY '<sys_language>';" | ddev import-db --no-drop
    echo "TRUNCATE TABLE tx_bgmhreflang_page_page_mm; LOAD XML LOCAL INFILE '/var/www/html/packages/bgm-hreflang/Tests/Functional/Fixtures/Database/tx_bgmhreflang_page_page_mm.xml' INTO TABLE tx_bgmhreflang_page_page_mm ROWS IDENTIFIED BY '<tx_bgmhreflang_page_page_mm>';" | ddev import-db --no-drop

Use this TypoScript setup on the root page:

..code::

    page = PAGE
    page.10 = USER
    page.10 {
        userFunc = BGM\BgmHreflang\Utility\HreflangTags->renderFrontendList
        stdWrap.htmlSpecialChars = 1
        stdWrap.br = 1
    }

And use this in your AdditionalConfiguration.php:

..code:: php

    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['bgm_hreflang'] = array(
        'countryMapping' => array(
            2 => array( //International
                'countryCode' => 'en',
                'languageMapping' => array(0 => 'en'),
                'domainName' => 'https://www.my-domain.com',
            ),
            8 => array( //Germany and Austria
                'countryCode' => 'de',
                'languageMapping' => array(0 => 'de'),
                'additionalCountries' => array('at'),
            ),
            14 => array( //Switzerland
                'countryCode' => 'ch',
                'languageMapping' => array(0 => 'de', 1 => 'it', 2 => 'fr',),
                'additionalGetParameters' => array(
                    0 => '&foo=bar',
                    2 => '&foo=bar&john=doe',
                ),
            ),
        ),
        'defaultCountryId' => 2,
    );

Extend bgm_hreflang
===================

There are a lot of signals at different places in the code. Feel free to use them :-)

Example
-------

If you have product records in each country branch, but the EAN is the same, you could connect the products detail
view automatically depending on the EAN:

.. code:: php

	//include this in your AdditionalConfiguration.php or your Theme-Extension's ext_localconf.php
	\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher')
		->connect(
			'BGM\\BgmHreflang\\Utility\\HreflangTags',
			'frontend_beforeRenderSingleTag',
			'BGM\\BgmTheme\\SignalSlot\\HreflangTags',
			'getGetParametersForProducts'
		);

See the implementation in :download:`the example script (EXT:bgm_hreflang/Documentation/Example/Products.php) <Example/Products.php>`.
Don't forget to connect the detail view pages in the backend! This class just adds the necessary GET parameters.