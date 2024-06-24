<?php

use TYPO3\CodingStandards\CsFixerConfig;

$config = CsFixerConfig::create();
$config->getFinder()
    ->in(__DIR__ . '/..')
;
return $config;
