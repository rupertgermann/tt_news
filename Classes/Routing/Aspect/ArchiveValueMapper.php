<?php
namespace RG\TtNews\Routing\Aspect;

use TYPO3\CMS\Core\Routing\Aspect\StaticMappableAspectInterface;
use TYPO3\CMS\Core\Site\SiteLanguageAwareTrait;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

class ArchiveValueMapper implements StaticMappableAspectInterface
{
    use SiteLanguageAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function generate(string $value): ?string
    {
        return $value !== false ? (string)$value : null;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(string $value): ?string
    {
        return isset($value) ? (string)$value : null;
    }

}
