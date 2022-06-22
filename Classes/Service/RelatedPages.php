<?php

namespace BGM\BgmHreflang\Service;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class RelatedPages
{

    /**
     * Get hreflang relations recursivly
     *
     * @param int $pageId
     * @param array $relatedPages
     */
    public static function buildRelations(int $pageId, array &$relatedPages)
    {
        if(!isset($relatedPages[$pageId])) {
            $relatedPages[$pageId] = $pageId;

            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('pages');
            $queryBuilder->getRestrictions()->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            $directRelations = $queryBuilder
                ->select('mm.*')
                ->from('tx_bgmhreflang_page_page_mm', 'mm')
                ->leftJoin('mm', 'pages', 'p', 'mm.uid_foreign = p.uid')
                ->where($queryBuilder->expr()->eq('mm.uid_local', (int)$pageId))
                ->execute()
                ->fetchAll();
            foreach ($directRelations as $directRelation) {
                if (!isset($relatedPages[$directRelation['uid_foreign']])) {
                    self::buildRelations($directRelation['uid_foreign'], $relatedPages);
                }
            }

            /** @var QueryBuilder $queryBuilder2 */
            $queryBuilder2 = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('pages');
            $queryBuilder2->getRestrictions()->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            $indirectRelations = $queryBuilder2
                ->select('mm.*')
                ->from('tx_bgmhreflang_page_page_mm', 'mm')
                ->leftJoin('mm', 'pages', 'p', 'mm.uid_local = p.uid')
                ->where($queryBuilder2->expr()->eq('mm.uid_foreign', (int)$pageId))
                ->execute()
                ->fetchAll();
            foreach ($indirectRelations as $indirectRelation) {
                if (!isset($relatedPages[$indirectRelation['uid_local']])) {
                    self::buildRelations($indirectRelation['uid_local'], $relatedPages);
                }
            }
        }
    }

}
