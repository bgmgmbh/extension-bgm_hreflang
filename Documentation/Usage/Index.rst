.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _usage:

Usage
=====

Editors can connect the pages from the different country branches in the page properties.

Example
-------

Pagetree
````````

.. sidebar:: Pagetree

	.. figure:: Images/Pagetree.png
		:alt: Example pagetree
		:align: center

- 61 International
- - 71 Page A
- - - sys_language_uid 0 => english
- - 81 Page B
- - - sys_language_uid 0 => english
- - 91 Page C
- - - sys_language_uid 0 => english
- - 101 Page D
- - - sys_language_uid 0 => english
- - - sys_language_uid 1 => german
- 111 Deutschland
- - 121 Seite A
- - - sys_language_uid 0 => german
- - 131 Seite B
- - - sys_language_uid 0 => german
- - 141 Seite C
- - - sys_language_uid 0 => german
- - - sys_language_uid 11 => english
- - 151 Seite D
- - - sys_language_uid 0 => german
- 161 Schweiz
- - 201 Seite A
- - - sys_language_uid 0 => german
- - - sys_language_uid 21 => italian
- - - sys_language_uid 31 => french
- - 191 Seite B
- - - sys_language_uid 0 => german
- - - sys_language_uid 21 => italian
- - 181 Seite C
- - - sys_language_uid 0 => german
- - - sys_language_uid 31 => french
- - 171 Seite D
- - - sys_language_uid 0 => german
- 211 Italia
- - 251 Pagina A
- - - sys_language_uid 0 => italian
- - 241 Pagina B
- - - sys_language_uid 0 => italian
- - 231 Pagina C
- - - sys_language_uid 0 => italian
- - 221 Pagina D
- - - sys_language_uid 0 => italian
- - - sys_language_uid 1 => german

Configuration
`````````````

.. code:: php

	$languageMapping = array(
		1 => 'de', //german
		11 => 'en', //english
		21 => 'it', //italian
		31 => 'fr', //french
	);
	$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['bgm_hreflang']['countryMapping'] = array(
		//Example
		61 => array( //International
			'countryCode' => 'en',
			'languageMapping' => $languageMapping + array(0 => 'en'),
		),
		111 => array( //Germany and Austria
			'countryCode' => 'de',
			'languageMapping' => $languageMapping + array(0 => 'de'),
			'additionalCountries' => array('at'),
		),
		161 => array( //Switzerland
			'countryCode' => 'ch',
			'languageMapping' => $languageMapping + array(0 => 'de'),
		),
		211 => array( //Italy
			'countryCode' => 'it',
			'languageMapping' => $languageMapping + array(0 => 'it'),
		),
	);
	$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['bgm_hreflang']['defaultCountryId'] = 61;

Output
``````

**1) The editor connected all A pages.**
So we have these hreflang tags on the A pages:

.. code:: html

	<link rel="alternate" hreflang="x-default" href="http://development.bgm.projects.localhost/index.php?id=71" />
	<link rel="alternate" hreflang="de-de" href="http://development.bgm.projects.localhost/index.php?id=121" />
	<link rel="alternate" hreflang="de-at" href="http://development.bgm.projects.localhost/index.php?id=121" />
	<link rel="alternate" hreflang="de-ch" href="http://development.bgm.projects.localhost/index.php?id=201" />
	<link rel="alternate" hreflang="it-ch" href="http://development.bgm.projects.localhost/index.php?id=201&L=21" />
	<link rel="alternate" hreflang="fr-ch" href="http://development.bgm.projects.localhost/index.php?id=201&L=31" />
	<link rel="alternate" hreflang="it-it" href="http://development.bgm.projects.localhost/index.php?id=251" />

**2) The editor connected the B pages 81, 131 and 241 (he has forgotten to connect the swiss B page 191 ;-)).**
So we have these hreflang tags on the B pages 81, 131 and 241:

.. code:: html

	<link rel="alternate" hreflang="x-default" href="http://development.bgm.projects.localhost/index.php?id=81" />
	<link rel="alternate" hreflang="de-de" href="http://development.bgm.projects.localhost/index.php?id=131" />
	<link rel="alternate" hreflang="de-at" href="http://development.bgm.projects.localhost/index.php?id=131" />
	<link rel="alternate" hreflang="it-it" href="http://development.bgm.projects.localhost/index.php?id=241" />

And we have these tags on the swiss B page 191:

.. code:: html

	<link rel="alternate" hreflang="de-ch" href="http://development.bgm.projects.localhost/index.php?id=191" />
	<link rel="alternate" hreflang="it-ch" href="http://development.bgm.projects.localhost/index.php?id=191&L=21" />

**3) The international C page 91 is connected to the german C page 141. And the german C page 141 is connected to the
italian C page 231.**

.. code:: html

	<link rel="alternate" hreflang="de-de" href="http://development.bgm.projects.localhost/index.php?id=141" />
	<link rel="alternate" hreflang="de-at" href="http://development.bgm.projects.localhost/index.php?id=141" />
	<link rel="alternate" hreflang="it-it" href="http://development.bgm.projects.localhost/index.php?id=231" />
	<link rel="alternate" hreflang="x-default" href="http://development.bgm.projects.localhost/index.php?id=91" />

**4) The swiss C page 181 is not connected to any other page and has a translation.**

.. code:: html

	<link rel="alternate" hreflang="de-ch" href="http://development.bgm.projects.localhost/index.php?id=181" />
	<link rel="alternate" hreflang="fr-ch" href="http://development.bgm.projects.localhost/index.php?id=181&L=31" />

**5) The international D page 101 is not connected to another page and has a translation.**

.. code:: html

	<link rel="alternate" hreflang="x-default" href="http://development.bgm.projects.localhost/index.php?id=101" />
	<link rel="alternate" hreflang="de" href="http://development.bgm.projects.localhost/index.php?id=101&L=1" />

**6) The german D page 151 is not connected to any other page and has no translation, but should be used for Austria, too.**

.. code:: html

	<link rel="alternate" hreflang="de-de" href="http://development.bgm.projects.localhost/index.php?id=151" />
	<link rel="alternate" hreflang="de-at" href="http://development.bgm.projects.localhost/index.php?id=151" />
