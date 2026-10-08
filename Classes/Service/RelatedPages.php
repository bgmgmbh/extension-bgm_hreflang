<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 extension "BGM hreflang".
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace BGM\BgmHreflang\Service;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Collects all pages that are connected to a page by hreflang relations.
 */
class RelatedPages
{
    /**
     * Adds the page and, recursively, all pages related to it in either direction to $relatedPages.
     *
     * @param array<int, int> $relatedPages Page uids, indexed by page uid
     */
    public static function buildRelations(int $pageId, array &$relatedPages): void
    {
        if (!isset($relatedPages[$pageId])) {
            $relatedPages[$pageId] = $pageId;

            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('pages');
            $queryBuilder->getRestrictions()->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            $directRelations = $queryBuilder
                ->select('mm.*')
                ->from('tx_bgmhreflang_page_page_mm', 'mm')
                ->leftJoin('mm', 'pages', 'p', 'mm.uid_foreign = p.uid')->where($queryBuilder->expr()->eq('mm.uid_local', $pageId))->executeQuery()->fetchAllAssociative();

            foreach ($directRelations as $directRelation) {
                if (!isset($relatedPages[$directRelation['uid_foreign']])) {
                    self::buildRelations((int)$directRelation['uid_foreign'], $relatedPages);
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
                ->leftJoin('mm', 'pages', 'p', 'mm.uid_local = p.uid')->where($queryBuilder2->expr()->eq('mm.uid_foreign', $pageId))->executeQuery()->fetchAllAssociative();
            foreach ($indirectRelations as $indirectRelation) {
                if (!isset($relatedPages[$indirectRelation['uid_local']])) {
                    self::buildRelations((int)$indirectRelation['uid_local'], $relatedPages);
                }
            }
        }
    }
}
