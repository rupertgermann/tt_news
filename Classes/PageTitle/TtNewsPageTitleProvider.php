<?php

declare(strict_types=1);

namespace RG\TtNews\PageTitle;

use TYPO3\CMS\Core\PageTitle\AbstractPageTitleProvider;

final class TtNewsPageTitleProvider extends AbstractPageTitleProvider
{
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }
}
