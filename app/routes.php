<?php
//routes.php
use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Application\Settings\SettingsInterface;
use App\Application\Handlers\Usuario;
use App\Application\Handlers\Campanha;
use App\Application\Handlers\Pedido;
use App\Application\Handlers\Pagamento;
use App\Application\Handlers\Bilhetes;
use App\Application\Handlers\Ranking;
use App\Application\Handlers\InformacaoSistema;
use Psr\Container\ContainerInterface;
use Slim\Exception\HttpUnauthorizedException;
use MercadoPago\SDK;
use GuzzleHttp\Client;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../src/Auth/auth.php';
require_once __DIR__ . '/../src/Auth/validate.php';

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
    // boas vindas ao sistema
    $app->get('/', function (Request $request, Response $response) {
        $response
            ->getBody()
            ->write('<strong>OFICINA ONLINE 2024</strong> - v.1');
        return $response;
    });


    $app->post('/login', function (Request $request, Response $response) {
        $data = $request->getParsedBody();

        return $response->withJson(['success' => true, 'usuario' => $data['email']]);

        global $container;

        $settings = $container->get(SettingsInterface::class);

        $secret_key = $settings->get('secret_key');

        $token = authenticateUser($this->get(PDO::class), $data['email'], md5($data['senha']), $secret_key);

        if ($token) {
            return $response->withJson(['success' => true, 'token' => $token['token'], 'usuario' => $token['usuario']]);
        } else {
            return $response->withStatus(401)->withJson(['error' => 'Credenciais inválidas']);
        }
    });


    $app->group('/usuario', function ($app) use ($validarTokenMiddleware) {
        $app->get('', Usuario\GetUsuario::class);
        $app->get('{id}', Usuario\GetUsuarioId::class);
        $app->post('', Usuario\PostUsuario::class); //POST não é protegido, porque qualquer um pode se cadastrar sem ter credencial.
        $app->put('{id}', Usuario\PutUsuarioId::class);
        $app->delete('{id}', Usuario\DeleteUsuarioId::class);
    });

    $app->options('/{routes:.+}', function ($request, $response, $args) {
        return $response;
    });
};
