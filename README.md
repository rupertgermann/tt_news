# tt_news

TYPO3 Extension - News

Website news with front page teasers and article handling inside.

### AKA: **"the generic record handler"**

#### Version 12.0.0

This tt_news version is currently in development and only compatible with TYPO3 v12.

See commit history for changes. There are breaking changes! Feel free to test and contribute.

#### Version 11.0.0

This tt_news version is compatible with TYPO3 v10 and v11.

The Backend Module "News Admin" has been dropped in TYPO3 v11

#### Version 10.1.0

NEW in 10.1: Added support to use Fluid Templating (example Templates included)

set `plugin.tt_news.useFluidRendering=1` in constants or setup to activate it.


#### Version 10.0.0

This tt_news version is compatible with TYPO3 v9 and v10.

Comes with tons of fixes, slug handling and [Routing Example](https://github.com/rupertgermann/tt_news/blob/master/Configuration/Routing/config.yaml)

Includes several new upgrade wizards:

* migrate news images to FAL (finally ;-) )
* migrate news files to FAL
* migrate news category images to FAL
* populate slug field

Thanks to all contributers for patches, PRs and testing.


## TYPO3 Extension Repository

[https://typo3.org/extensions/repository/view/tt_news](https://typo3.org/extensions/repository/view/tt_news)



## Composer

[https://packagist.org/packages/rupertgermann/tt_news](https://packagist.org/packages/rupertgermann/tt_news)

    composer require rupertgermann/tt_news

## Compatibility

branch master requires at least TYPO3 10.4 and is compatible with TYPO3 10.4 LTS and TYPO3 11.5 LTS.

For tt_news for older TYPO3 versions take a look at [https://extensions.typo3.org/extension/tt_news/](https://extensions.typo3.org/extension/tt_news/)  and scroll down to "Version History".

