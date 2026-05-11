<?php

declare(strict_types=1);

namespace RG\TtNews\Upgrades;

use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\AbstractListTypeToCTypeUpdate;

#[UpgradeWizard('ttNewsPluginListTypeToCTypeUpdate')]
final class PluginListTypeToCTypeUpdate extends AbstractListTypeToCTypeUpdate
{
    protected function getListTypeToCTypeMapping(): array
    {
        return [
            '9' => 'tt_news',
        ];
    }

    public function getTitle(): string
    {
        return 'Migrate tt_news plugins from list_type to CType';
    }

    public function getDescription(): string
    {
        return 'Migrate tt_news plugins from list_type to CType definition.';
    }
}
