<?php

namespace RG\TtNews\Tree\TableConfiguration;

use RG\TtNews\Database\Database;
use RG\TtNews\Utility\Div;
use TYPO3\CMS\Backend\Tree\TreeNode;
use TYPO3\CMS\Backend\Tree\TreeNodeCollection;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeDataProvider;


/**
 * TCA tree data provider
 */
class NewsDatabaseTreeDataProvider extends DatabaseTreeDataProvider
{
    /**
     * Gets node children
     *
     * @param TreeNode $node
     * @param int      $level
     *
     * @return NULL|TreeNodeCollection
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function getChildrenOf(TreeNode $node, $level): ?TreeNodeCollection
    {
        $allowedItems = $this->getBeUser()->getTSConfig()['tt_newsPerms.']['tt_news_cat.']['allowedItems'];

        $allowedItems = $allowedItems ? GeneralUtility::intExplode(',', $allowedItems) : Div::getAllowedTreeIDs();

        $storage = null;

        if ($node->getId() !== 0 && !in_array($node->getId(), $allowedItems)) {
            return null;
        }

        $nodeData = null;
        if ($node->getId() !== 0) {
            $nodeData = Database::getInstance()->exec_SELECTgetSingleRow(
                '*',
                $this->tableName,
                'uid=' . $node->getId()
            );
        }

        if ($nodeData == null) {
            $nodeData = [
                'uid' => 0,
                $this->getLookupField() => ''
            ];
        }

        $children = $this->getRelatedRecords($nodeData);
        if (empty($children)) {
            return null;
        }

        /** @var $storage TreeNodeCollection */
        $storage = GeneralUtility::makeInstance(TreeNodeCollection::class);
        foreach ($children as $child) {
            /** @var TreeNode $node */
            $node = GeneralUtility::makeInstance(TreeNode::class);

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

    /**
     * @return BackendUserAuthentication
     */
    protected function getBeUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
