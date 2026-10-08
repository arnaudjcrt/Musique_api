<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class JwtMiddleware
{
    public function __invoke(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {

        $authorization = $request->getHeaderLine('Authorization');

        if (
            empty($authorization) ||
            !str_starts_with($authorization, 'Bearer ')
        ) {
            $response = new Response();

            $response->getBody()->write(
                json_encode([
                    'error' => 'Token manquant'
                ])
            );

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }

        $token = substr($authorization, 7);

        $decoded = JwtHelper::validateToken($token);

        if ($decoded === null) {
            $response = new Response();

            $response->getBody()->write(
                json_encode([
                    'error' => 'Token invalide ou expire'
                ])
            );

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }

        return $handler->handle($request);
    }
}