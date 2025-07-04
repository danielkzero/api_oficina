<?php

namespace App\Application\Handlers\Api;

use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;


class Online
{
    public static function registerRoutes(App $app, $validarTokenMiddleware)
    {
        $app->get('', function (Request $request, Response $response) {
            $apresentation = self::apresentation();
            $response->getBody()->write($apresentation);
            return $response->withHeader('Content-Type', 'text/html');
        });
        $app->get('/', function (Request $request, Response $response) {
            $apresentation = self::apresentation();
            $response->getBody()->write($apresentation);
            return $response->withHeader('Content-Type', 'text/html');
        });
    }
    private static function apresentation()
    {
        return <<<HTML
        <div class="versao-display">
            <strong>CMS MODEST 2025 • v.1.0.0</strong>
        </div>
        <style>
            body, html {
                margin: 0px; 
                padding: 0px;
                font-family: arial;
            }
            .versao-display {
                display: flex; 
                justify-content: center; 
                align-items: center; 
                height: 100vh; 
                width: 100%;
            }
        </style>
        HTML;
    }
}
