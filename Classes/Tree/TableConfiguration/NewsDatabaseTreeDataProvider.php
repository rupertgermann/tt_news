<?php

namespace RG\TtNews\Tree\TableConfiguration;

use Doctrine\DBAL\DBALException;
use RG\TtNews\Database\Database;
use RG\TtNews\Utility\Div;
use TYPO3\CMS\Backend\Tree\SortedTreeNodeCollection;
use TYPO3\CMS\Backend\Tree\TreeNode;
use TYPO3\CMS\Backend\Tree\TreeNodeCollection;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeDataProvider;
use TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeNode;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * TCA tree data provider
 *
 * @todo fix for TYPO3 v12
 */
class NewsDatabaseTreeDataProvider extends DatabaseTreeDataProvider
{
    /**
     * Gets node children
     *
     * @param TreeNode $node
     * @param int      $level
     *
     * @return TreeNodeCollection|null
     * @throws DBALException
     */
    protected function getChildrenOf(TreeNode $node, $level): ?TreeNodeCollection
    {
        $allowedItems = $this->getBeUser()->getTSConfig()['tt_newsPerms.']['tt_news_cat.']['allowedItems'] ?? false;
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
                $this->getLookupField() => '',
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
                $this->setItemUnselectableList(array_merge($this->getItemUnselectableList() ?? [], [$child]));
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
     * Builds a complete node including childs
     *
     * @param TreeNode $basicNode
     * @param DatabaseTreeNode|null $parent
     * @param int $level
     * @return DatabaseTreeNode Node object
     */
    protected function buildRepresentationForNode(TreeNode $basicNode, DatabaseTreeNode $parent = null, $level = 0)
    {
        /** @var DatabaseTreeNode $node */
        $node = GeneralUtility::makeInstance(DatabaseTreeNode::class);
        $row = [];
        if ($basicNode->getId() == 0) {
            $node->setSelected(false);
            $node->setExpanded(true);
            $node->setLabel($this->getLanguageService()->sL($GLOBALS['TCA'][$this->tableName]['ctrl']['title']));
        } else {
            $row = BackendUtility::getRecordWSOL($this->tableName, (int)$basicNode->getId(), '*', '', false);
            $node->setLabel(BackendUtility::getRecordTitle($this->tableName, $row) ?: $basicNode->getId());
            $node->setSelected(GeneralUtility::inList($this->getSelectedList(), $basicNode->getId()));
            $node->setExpanded($this->isExpanded($basicNode));
        }
        $node->setId($basicNode->getId());
        $node->setSelectable(!GeneralUtility::inList($this->getNonSelectableLevelList(), (string)$level) && !in_array($basicNode->getId(), $this->getItemUnselectableList()));
        $node->setSortValue($this->nodeSortValues[$basicNode->getId()] ?? '');

        /** @var IconFactory $iconFactory */
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);

        if (in_array($basicNode->getId(), $this->getItemUnselectableList())) {
            $iconIdentifier = $iconFactory->mapRecordTypeToIconIdentifier($this->tableName, $row);
            $node->setIcon($iconFactory->getIcon($iconIdentifier, Icon::SIZE_SMALL, 'overlay-readonly'));
            if (GeneralUtility::inList($this->getSelectedList(), $basicNode->getId())) {
                $node->setLabel('[X] ' . $node->getLabel());
            }
        } else {
            $node->setIcon($iconFactory->getIconForRecord($this->tableName, $row, Icon::SIZE_SMALL));
        }

        $node->setParentNode($parent);
        if ($basicNode->hasChildNodes()) {
            $node->setHasChildren(true);
            /** @var SortedTreeNodeCollection $childNodes */
            $childNodes = GeneralUtility::makeInstance(SortedTreeNodeCollection::class);
            $tempNodes = [];
            foreach ($basicNode->getChildNodes() as $child) {
                $tempNodes[] = $this->buildRepresentationForNode($child, $node, $level + 1);
            }
            $childNodes->exchangeArray($tempNodes);
            $childNodes->asort();
            $node->setChildNodes($childNodes);
        }
        return $node;
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBeUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
