<?php

use RG\TtNews\Middleware\AjaxResolver;
/**
 * An array consisting of implementations of middlewares for a middleware stack to be registered
 *  'stackname' => [
 *      'middleware-identifier' => [
 *         'target' => classname or callable
 *         'before/after' => array of dependencies
 *      ]
 *   ]
 */
return [
    'frontend' => [
        'rg/tt-news/ajax-resolver' => [
            'target' => AjaxResolver::class,
            'after' => [
                'typo3/cms-frontend/authentication',
            ],
        ]
    ]
];
