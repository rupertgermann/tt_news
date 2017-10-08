<?php
namespace RG\TtNews\Tree\TableConfiguration;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeDataProvider;

use RG\TtNews\tx_ttnews_div;

/**
 * TCA tree data provider
 */
class NewsDatabaseTreeDataProvider extends DatabaseTreeDataProvider
{
    /**
     * Gets node children
     *
     * @param \TYPO3\CMS\Backend\Tree\TreeNode $node
     * @param int $level
     * @return NULL|\TYPO3\CMS\Backend\Tree\TreeNodeCollection
     */
    protected function getChildrenOf(\TYPO3\CMS\Backend\Tree\TreeNode $node, $level)
    {
        $allowedItems = $GLOBALS['BE_USER']->getTSConfigVal('tt_newsPerms.tt_news_cat.allowedItems');
        $allowedItems = $allowedItems ? \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $allowedItems) : tx_ttnews_div::getAllowedTreeIDs();

        $storage = null;
        
        if ($node->getId() !== 0 && !in_array($node->getId(), $allowedItems))
        {
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

            if (!in_array($child, $allowedItems))
            {
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
