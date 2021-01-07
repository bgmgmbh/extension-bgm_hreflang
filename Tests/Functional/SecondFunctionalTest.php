<?php

namespace BGM\BgmHreflang\Tests\Functional;

/**
 * Class SecondFunctionalTest
 *
 *
 * Difference to FirstFunctionalTest:
 * $configurationToUseInTestInstance['EXTCONF']['bgm_hreflang']['countryMapping'][2]['countryCode'] = '';
 *
 *
 * https://de.slideshare.net/cpsitgmbh/functional-tests-with-typo3
 * typo3DatabaseName="bgm_hreflang" typo3DatabaseUsername="project" typo3DatabasePassword="project" typo3DatabaseHost="127.0.0.1" typo3DatabasePort="3308" TYPO3_PATH_WEB="$PWD/.Build/Web" $PWD/.Build/bin/phpunit -c $PWD/.Build/vendor/nimut/testing-framework/res/Configuration/FunctionalTests.xml $PWD/.Build/Web/typo3conf/ext/bgm_hreflang/Tests/Functional
 */
class SecondFunctionalTest extends FirstFunctionalTest
{
    protected $configurationToUseInTestInstance = [
        'EXTCONF' => [
            'bgm_hreflang' => [
                'countryMapping' => [
                    2 => [ //International
                        'countryCode' => '',
                        'languageMapping' => [0 => 'en', 1 => 'it'],
                        'domainName' => 'https://www.my-domain.com',
                    ],
                    8 => [ //Germany and Austria
                        'countryCode' => 'de',
                        'languageMapping' => [0 => 'de'],
                        'additionalCountries' => ['at'],
                    ],
                    14 => [ //Switzerland
                        'countryCode' => 'ch',
                        'languageMapping' => [0 => 'de', 1 => 'it', 2 => 'fr'],
                        'additionalGetParameters' => [
                            0 => '&foo=bar',
                            2 => '&foo=bar&john=doe',
                        ],
                    ],
                ],
                'defaultCountryId' => 2,
            ],
        ],
    ];
}
