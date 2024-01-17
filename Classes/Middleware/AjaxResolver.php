<?php
/**
 * Created by PhpStorm.
 * User: rupertgermann
 * Date: 05.10.19
 * Time: 16:43
 */

namespace RG\TtNews\Middleware;

use Doctrine\DBAL\DBALException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RG\TtNews\Menu\Catmenu;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class AjaxResolver implements MiddlewareInterface
{
    /**
     * The local configuration array
     *
     * @var array
     */
    protected $conf;

    /**
     * Process an incoming server request.
     *
     * Processes an incoming server request in order to produce a response.
     * If unable to produce the response itself, it may delegate to the provided
     * request handler to do so.
     *
     * @param ServerRequestInterface  $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     * @throws DBALException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $eID = $request->getParsedBody()['ttnewsID'] ?? $request->getQueryParams()['ttnewsID'] ?? null;

        if ($eID === null) {
            return $handler->handle($request);
        }

        $parsedBody = $request->getQueryParams();

        $this->conf = [
            'action' => $parsedBody['action'] ?? null,
            'PM' => $parsedBody['PM'] ?? null,
            'id' => (int)$parsedBody['id'] ?? null,
            'cObjUid' => (int)$parsedBody['cObjUid'] ?? null,
            'L' => (int)$parsedBody['L'] ?? null,
        ];

        /** @var Response $response */
        $response = GeneralUtility::makeInstance(Response::class);

        // Basic test for required value
        if ($this->conf['action'] === null) {
            $response->getBody()->write('This script cannot be called directly');
            $response = $response->withStatus(500);
            return $response;
        }
        $content = '';

        match ($this->conf['action']) {
            'expandTree' => $content .= $this->expandTree(),
            default => throw new \UnexpectedValueException('method not allowd', 1_565_010_424),
        };

        $response->getBody()->write((string)$content);

        return $response;
    }

    /**
     * @return string
     * @throws DBALException
     */
    private function expandTree()
    {
        $module = new Catmenu();

        return $module->ajaxExpandCollapse($this->conf);
    }
}
