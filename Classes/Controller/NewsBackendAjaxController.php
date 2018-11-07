<?php
namespace RG\TtNews\Controller;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RG\TtNews\Module\NewsAdminModule;

/**
 * Class NewsBackendAjaxController
 *
 * @package RG\TtNews\Controller
 */
class NewsBackendAjaxController
{
    /**
     * The local configuration array
     *
     * @var array
     */
    protected $conf;


    /**
     * The constructor of this class
     */
    public function __construct()
    {


    }

    /**
     * The main dispatcher function. Collect data and prepare HTML output.
     *
     * @param ServerRequestInterface $request
     *
     * @param ResponseInterface      $response
     *
     * @return ResponseInterface
     * @throws \Doctrine\DBAL\DBALException
     */
    public function dispatch(ServerRequestInterface $request, ResponseInterface $response)
    {
        $parsedBody = $request->getQueryParams();

        $this->conf = [
            'category' => $parsedBody['category'] ?? null,
            'pid' => (int)$parsedBody['pid'] ?? null,
            'id' => (int)$parsedBody['id'] ?? null,
            'PM' => (int)$parsedBody['PM'] ?? null,
            'action' => $parsedBody['action'] ?? null,
        ];

        $response = $response->withHeader('Content-Type', 'text/html; charset=utf-8');

        // Basic test for required value
        if ($this->conf['action'] === null) {
            $response->getBody()->write('This script cannot be called directly');
            $response = $response->withStatus(500);
            return $response;
        }
        $content = '';

        // Determine the scripts to execute
        switch ($this->conf['action']) {
            case 'loadList':
                $content .= $this->loadList();
                break;
            case 'expandTree':
                $content .= $this->expandTree();
                break;
            default:
                $content .= 'no action given';
        }
        $response->getBody()->write($content);

        return $response;
    }

    /**
     * @return string
     * @throws \Doctrine\DBAL\DBALException
     */
    private function loadList()
    {
        $module = new NewsAdminModule();
        $content = $module->ajaxLoadList($this->conf);
        return $content;
    }

    /**
     * @return string
     * @throws \Doctrine\DBAL\DBALException
     */
    private function expandTree()
    {
        $module = new NewsAdminModule();
        $content = $module->ajaxExpandCollapse($this->conf);
        return $content;
    }

}