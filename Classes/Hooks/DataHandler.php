<?php

namespace BGM\BgmHreflang\Hooks;

use BGM\BgmHreflang\Service\RelatedPages;
use TYPO3\CMS\Core\Utility\MathUtility;

class DataHandler
{

    /**
     * Register old related pages for cache clearing
     *
     * @param $incomingFieldArray
     * @param $table
     * @param $id
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler
     */
    public function processDatamap_preProcessFieldArray($incomingFieldArray, $table, $id, \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler) {
        if($table === 'pages' && MathUtility::canBeInterpretedAsInteger($id)) {
            $relatedPages = [];
            RelatedPages::buildRelations($id, $relatedPages);
            foreach ($relatedPages as $relatedPage) {
                $dataHandler->registerRecordIdForPageCacheClearing($table, $relatedPage);
            }
        }
    }

    /**
     * Register new related pages for cache clearing
     *
     * @param $status
     * @param $table
     * @param $id
     * @param $fieldArray
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler
     */
    public function processDatamap_afterDatabaseOperations($status, $table, $id, $fieldArray, \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler) {
        if($table === 'pages') {
            if(!MathUtility::canBeInterpretedAsInteger($id)){
                $id = $dataHandler->substNEWwithIDs[$id];
            }
            $relatedPages = [];
            RelatedPages::buildRelations($id, $relatedPages);
            foreach ($relatedPages as $relatedPage) {
                $dataHandler->registerRecordIdForPageCacheClearing($table, $relatedPage);
            }
        }
    }

}
