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