<?php

namespace RG\TtNews\Routing\Aspect;

use TYPO3\CMS\Core\Routing\Aspect\StaticMappableAspectInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteLanguageAwareInterface;

class ArchiveValueMapper implements StaticMappableAspectInterface, SiteLanguageAwareInterface
{
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
    protected SiteLanguage $siteLanguage;

    public function setSiteLanguage(SiteLanguage $siteLanguage)
    {
        // TODO: Implement setSiteLanguage() method.
    }

    public function getSiteLanguage(): SiteLanguage
    {
        // TODO: Implement getSiteLanguage() method.
    }
}
