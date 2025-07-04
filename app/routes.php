<?php
//routes.php
use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Application\Settings\SettingsInterface;
use App\Application\Handlers\Api\Usuarios;
use App\Application\Handlers\Api\Online;
use App\Application\Handlers\Api\Paginas;
use App\Application\Handlers\Api\Esquema;
use App\Application\Handlers\Api\Empresa;
use Psr\Container\ContainerInterface;
use Slim\Exception\HttpUnauthorizedException;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../src/Auth/auth.php';

$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
});

$validarTokenMiddleware = function (Request $request, $handler) {
    try {
        ValidarToken($request, $this->get(ContainerInterface::class));
    } catch (Exception $e) {
        throw new HttpUnauthorizedException($request, $e->getMessage());
    }
    return $handler->handle($request);
};

return function (App $app) use ($validarTokenMiddleware) {

    // Verificar sistema online
    Online::registerRoutes($app, $validarTokenMiddleware);

    // Rotas de usuarios
    Usuarios::registerRoutes($app, $validarTokenMiddleware);

    // Rotas de páginas
    Paginas::registerRoutes($app, $validarTokenMiddleware);

    // Rotas de esquema
    Esquema::registerRoutes($app, $validarTokenMiddleware);

    // Rotas de empresa
    Empresa::registerRoutes($app, $validarTokenMiddleware);

    // Options para CORS
    $app->options('/{routes:.+}', function ($request, $response, $args) {
        return $response;
    });
};
