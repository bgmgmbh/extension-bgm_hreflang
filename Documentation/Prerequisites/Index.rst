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

Since version 4.0 you have to use the new page translation without the table "pages_language_overlay"!