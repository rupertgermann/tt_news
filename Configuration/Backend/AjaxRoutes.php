<?php

/**
 * Definitions for routes provided by EXT:tt_news
 */

return [
    'tt_news_backend_module' => [
        'path' => '/tt_news/backend/module',
        'target' => RG\TtNews\Controller\NewsBackendAjaxController::class . '::dispatch'
    ]
];
