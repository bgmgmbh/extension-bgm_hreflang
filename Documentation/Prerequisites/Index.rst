.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _prerequisites:

Prerequisites
=============

* One page tree per country ("country branch")
* Root of each country branch has the option "Use as Root Page" set
* Crosslinks between the country branches work. Perhaps you have to set `config.typolinkCheckRootline = 1` and `config.typolinkEnableLinksAcrossDomains = 1` in your TypoScript setup.
* If you have MountPages, try to set `config.MP_disableTypolinkClosestMPvalue = 1` in your TypoScript setup.
* If you activated the EXT:seo (typo3/cms-seo), you should disable it's hreflang generation
* * in TYPO3 9.5LTS with `unset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['TYPO3\CMS\Frontend\Page\PageGenerator']['generateMetaTags']['hreflang']);` in the AdditionalConfiguration.php or your Theme-Extension's ext_localconf.php
* * in TYPO3 10.4LTS by not setting `hreflang` in your site config `languages` (or wait for EXT:bgm_hreflang version 5 :-))

Since version 4.0 you have to use the new page translation without the table "pages_language_overlay"!
