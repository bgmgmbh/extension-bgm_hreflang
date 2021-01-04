<?php
namespace BGM\BgmHreflang\Tests\Functional;

/**
 * Class FirstFunctionalTest
 * https://de.slideshare.net/cpsitgmbh/functional-tests-with-typo3
 * typo3DatabaseName="bgm_hreflang" typo3DatabaseUsername="project" typo3DatabasePassword="project" typo3DatabaseHost="127.0.0.1" typo3DatabasePort="3308" TYPO3_PATH_WEB="$PWD/.Build/Web" $PWD/.Build/bin/phpunit -c $PWD/.Build/vendor/nimut/testing-framework/res/Configuration/FunctionalTests.xml $PWD/.Build/Web/typo3conf/ext/bgm_hreflang/Tests/Functional
 *
 * @package BGM\BgmHreflang\Tests\Functional
 */
class FirstFunctionalTest extends \Nimut\TestingFramework\TestCase\FunctionalTestCase {

    /**
     * Ensure your extension is loaded
     *
     * @var array
     */
    protected $testExtensionsToLoad = array(
        'typo3conf/ext/bgm_hreflang',
        );

    protected $configurationToUseInTestInstance = array(
        'EXTCONF' => array(
            'bgm_hreflang' => array(
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
            ),
        ),
    );

    protected $fixturePath;

    protected function setUp()
    {
        parent::setUp();

        $this->fixturePath = ORIGINAL_ROOT . 'typo3conf/ext/bgm_hreflang/Tests/Functional/Fixtures/';

        // Import own fixtures
        $this->importDataSet($this->fixturePath . 'Database/pages.xml');
        $this->importDataSet($this->fixturePath . 'Database/sys_language.xml');
        $this->importDataSet($this->fixturePath . 'Database/tx_bgmhreflang_page_page_mm.xml');
        $this->importDataSet($this->fixturePath . 'Database/be_users.xml');

        // Set up the frontend!
        $this->setUpFrontendRootPage(2, // page id
            array( // array of TypoScript files which should be included
                $this->fixturePath . 'Frontend/Page.ts'
            ),
            array(
                2 => $this->fixturePath . 'Frontend/site-int.yaml',
            ));
        $this->setUpFrontendRootPage(8, // page id
            array( // array of TypoScript files which should be included
                $this->fixturePath . 'Frontend/Page.ts'
            ),
            array(
                8 => $this->fixturePath . 'Frontend/site-de.yaml',
            ));
        $this->setUpFrontendRootPage(14, // page id
            array( // array of TypoScript files which should be included
                $this->fixturePath . 'Frontend/Page.ts'
            ),
            array(
                14 => $this->fixturePath . 'Frontend/site-ch.yaml'
            ));
    }

    /**
     * Page "International-1" is connected with "Deutschland-1" and "Schweiz-1"
     *
     * @test
     */
    public function international1PageOutput()
    {

        $response = $this->getFrontendResponse(3);
        $this->assertEquals(
            trim('
<link rel="alternate" hreflang="de-at" href="https://localhost.de/Deutschland-1" />
<link rel="alternate" hreflang="de-ch" href="https://localhost.ch/Schweiz-1?foo=bar&cHash=7a5f8a7975c1f91d259d2bb88dccf0df" />
<link rel="alternate" hreflang="de-de" href="https://localhost.de/Deutschland-1" />
<link rel="alternate" hreflang="fr-ch" href="https://localhost.ch/fr/Schweiz-1-FR?foo=bar&john=doe&cHash=5afbb5ff7a67583390b09be874629980" />
<link rel="alternate" hreflang="it-ch" href="https://localhost.ch/it/Schweiz-1-IT" />
<link rel="alternate" hreflang="x-default" href="https://www.my-domain.com/International-1" />
            '),
            trim($response->getContent())
        );
    }

    /**
     * Page "International-2" is connected with "Deutschland-2" and mounted to "Schweiz-2"
     *
     * @test
     */
    public function international2PageOutput()
    {

        $response = $this->getFrontendResponse(4);
        $this->assertEquals(
            trim('
<link rel="alternate" hreflang="de-at" href="https://localhost.de/Deutschland-2" />
<link rel="alternate" hreflang="de-ch" href="https://localhost.ch/Schweiz-2/?foo=bar&cHash=fb805caf50da7b828f05388cde30f48d" />
<link rel="alternate" hreflang="de-de" href="https://localhost.de/Deutschland-2" />
<link rel="alternate" hreflang="x-default" href="https://www.my-domain.com/International-2" />
            '),
            trim($response->getContent())
        );
    }

    /**
     * Page "International-3" is connected with "Deutschland-3" and "Deutschland-3" is connected to "Schweiz-3"
     *
     * @test
     */
    public function international3PageOutput()
    {

        $response = $this->getFrontendResponse(6);
        $this->assertEquals(
            trim('
<link rel="alternate" hreflang="de-at" href="https://localhost.de/Deutschland-3" />
<link rel="alternate" hreflang="de-ch" href="https://localhost.ch/Schweiz-3?foo=bar&cHash=12f678c69502e2f1fb75c2ecbcc90cbb" />
<link rel="alternate" hreflang="de-de" href="https://localhost.de/Deutschland-3" />
<link rel="alternate" hreflang="it-ch" href="https://localhost.ch/it/Schweiz-3-IT" />
<link rel="alternate" hreflang="x-default" href="https://www.my-domain.com/International-3" />
            '),
            trim($response->getContent())
        );
    }

    /**
     * Page "International-4" is not connected
     *
     * @test
     */
    public function international4PageOutput()
    {

        $response = $this->getFrontendResponse(7);
        $this->assertEquals(
            trim('
<link rel="alternate" hreflang="x-default" href="https://www.my-domain.com/International-4" />
            '),
            trim($response->getContent())
        );
    }

    /**
     * Page "Deutschland-4" is not connected
     *
     * @test
     */
    public function deutschland4PageOutput()
    {

        $response = $this->getFrontendResponse(13);
        $this->assertEquals(
            trim('
<link rel="alternate" hreflang="de-at" href="https://localhost.de/Deutschland-4" />
<link rel="alternate" hreflang="de-de" href="https://localhost.de/Deutschland-4" />
            '),
            trim($response->getContent())
        );
    }

    /**
     * Page "Schweiz-4" is not connected
     *
     * @test
     */
    public function schweiz4PageOutput()
    {

        $response = $this->getFrontendResponse(18);
        $this->assertEquals(
            trim('
<link rel="alternate" hreflang="de-ch" href="https://localhost.ch/Schweiz-4?foo=bar&cHash=4d8a62b1a79ee2ddddabca654e2c3932" />
<link rel="alternate" hreflang="fr-ch" href="https://localhost.ch/fr/Schweiz-4-FR?foo=bar&john=doe&cHash=2d8550c63aa242df7d48ace518a32d91" />
            '),
            trim($response->getContent())
        );
    }
}
