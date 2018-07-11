<?php

/*
 * Copyright notice
 *
 * (c) 2004-2018 Rupert Germann <rupi@gmx.li>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

namespace RG\TtNews\Tree\TableConfiguration;

use RG\TtNews\Div;
use TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeDataProvider;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * TCA tree data provider
 */
class NewsDatabaseTreeDataProvider extends DatabaseTreeDataProvider
{
    /**
     * Gets node children
     *
     * @param \TYPO3\CMS\Backend\Tree\TreeNode $node
     * @param int                              $level
     *
     * @return NULL|\TYPO3\CMS\Backend\Tree\TreeNodeCollection
     */
    protected function getChildrenOf(\TYPO3\CMS\Backend\Tree\TreeNode $node, $level)
    {
        $allowedItems = $GLOBALS['BE_USER']->getTSConfigVal('tt_newsPerms.tt_news_cat.allowedItems');
        $allowedItems = $allowedItems ? \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(
            ',',
            $allowedItems
        ) : Div::getAllowedTreeIDs();

        $storage = null;

        if ($node->getId() !== 0 && !in_array($node->getId(), $allowedItems)) {
            return $storage;
        }

        $nodeData = null;
        if ($node->getId() !== 0) {
            $nodeData = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', $this->tableName, 'uid=' . $node->getId());
        }

        if ($nodeData == null) {
            $nodeData = [
                'uid' => 0,
                $this->getLookupField() => ''
            ];
        }

        $children = $this->getRelatedRecords($nodeData);
        if (empty($children)) {
            return $storage;
        }

        /** @var $storage \TYPO3\CMS\Backend\Tree\TreeNodeCollection */
        $storage = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Tree\TreeNodeCollection::class);
        foreach ($children as $child) {
            $node = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Tree\TreeNode::class);

            if (!in_array($child, $allowedItems)) {
                continue;
            }

            $node->setId($child);

            if ($level < $this->levelMaximum) {
                $children = $this->getChildrenOf($node, $level + 1);

                if ($children !== null) {
                    $node->setChildNodes($children);
                }
            }

            $storage->append($node);
        }

        return $storage;
    }
}
