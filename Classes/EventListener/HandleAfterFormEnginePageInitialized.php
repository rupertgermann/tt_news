<?php

namespace RG\TtNews\EventListener;

use RG\TtNews\Database\Database;
use RG\TtNews\Utility\Div;
use TYPO3\CMS\Backend\Controller\Event\AfterFormEnginePageInitializedEvent;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class HandleAfterFormEnginePageInitialized
{
    /**
     */
    public function __invoke(AfterFormEnginePageInitializedEvent $event): void
    {
        $request = $event->getRequest();
        $queryParams = $request->getQueryParams();

        if (!$this->getBeUser()->isAdmin()) {
            foreach ($queryParams['edit'] ?? [] as $table => $conf) {
                if ($table == 'tt_news') {
                    foreach ($conf as $id => $command) {
                        if ($command === 'edit') {
                            // get categories from the (untranslated) record in db
                            $res = Database::getInstance()->exec_SELECT_mm_query(
                                'tt_news_cat.uid, tt_news_cat.title',
                                'tt_news',
                                'tt_news_cat_mm',
                                'tt_news_cat',
                                ' AND tt_news_cat.deleted=0 AND tt_news_cat_mm.uid_local=' . (int)$id . BackendUtility::BEenableFields(
                                    'tt_news_cat'
                                )
                            );
                            $categories = [];
                            while (($row = Database::getInstance()->sql_fetch_assoc($res))) {
                                $categories[$row['uid']] = $row['title'];
                            }

                            $notAllowedItems = [];

                            $allowedItems = $this->getBeUser()->getTSConfig()['tt_newsPerms.']['tt_news_cat.']['allowedItems'] ?? '';
                            $allowedItems = $allowedItems ? GeneralUtility::intExplode(
                                ',',
                                $allowedItems
                            ) : Div::getAllowedTreeIDs();

                            foreach ($categories as $categoryId => $categoryTitle) {
                                if (!in_array($categoryId, $allowedItems)) {
                                    $notAllowedItems[] = $categoryTitle . ' (id=' . $categoryId . ')';
                                }
                            }

                            if (!empty($notAllowedItems)) {
                                $notAllowedItemsMessage = $this->getLanguageService()
                                    ->sL('LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xml:tt_news.notAllowedCategoryWarning') . implode(
                                        ', ',
                                        $notAllowedItems
                                    );

                                $flashMessage = GeneralUtility::makeInstance(
                                    FlashMessage::class,
                                    $notAllowedItemsMessage,
                                    '',
                                    ContextualFeedbackSeverity::WARNING
                                );
                                $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
                                $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
                                $defaultFlashMessageQueue->enqueue($flashMessage);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBeUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Returns LanguageService
     *
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
