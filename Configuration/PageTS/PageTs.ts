mod.wizards {
	newContentElement.wizardItems {
		plugins {
			elements {
				plugins_tx_ttnews_pi {
					icon = EXT:tt_news/Resources/Public/Images/ContentElementWizardIcon.gif
					title = LLL:EXT:tt_news/Resources/Private/Language/locallang_db_new_content_el.xlf:tt_news_title
					description = LLL:EXT:tt_news/Resources/Private/Language/locallang_db_new_content_el.xlf:tt_news_description
					tt_content_defValues {
						CType = list
						list_type = 9
					}
				}
			}
		}
	}
}

# RTE mode in table "tt_news"
RTE.config.tt_news.bodytext.proc.overruleMode = ts_css

TCEFORM.tt_news.bodytext.RTEfullScreenWidth = 100%

mod.web_txttnewsM1 {
	catmenu {
		expandFirst = 1

		show {
			cb_showEditIcons = 1
			cb_expandAll = 1
			cb_showHiddenCategories = 1

			btn_newCategory = 1
		}
	}
	list {
		limit = 15
		pidForNewArticles =
		fList = pid,uid,title,datetime,archivedate,tstamp,category;author
		icon = 1
		searchFields = uid,title,short,bodytext

		# configures the behavior of the record-title link. Possible values:
		# edit: link editform, view: link FE singleView, any other value: no link
		clickTitleMode = edit

		noListWithoutCatSelection = 0

		show {
			cb_showOnlyEditable = 1
			cb_showThumbs = 1
			search = 1

		}
		imageSize = 50

	}
	defaultLanguageLabel =
}