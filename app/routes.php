<?php
//routes.php
use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Application\Settings\SettingsInterface;
use App\Application\Handlers\Cliente;
use App\Application\Handlers\ClienteContato;
use App\Application\Handlers\ClienteContatoEmail;
use App\Application\Handlers\ClienteContatoTelefone;
use App\Application\Handlers\ClienteEmail;
use App\Application\Handlers\ClienteEndereco;
use App\Application\Handlers\ClienteExtra;
use App\Application\Handlers\ClienteTelefone;
use App\Application\Handlers\CondicaoPagamento;
use App\Application\Handlers\FormaPagamento;
use App\Application\Handlers\ICMS_ST;
use App\Application\Handlers\Pedido;
use App\Application\Handlers\Produto;
use App\Application\Handlers\ProdutoCategoria;
use App\Application\Handlers\ProdutoImagem;
use App\Application\Handlers\TabelaPreco;
use App\Application\Handlers\TabelaProdutoPreco;
use App\Application\Handlers\Usuario;

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

        global $container;

        $settings = $container->get(SettingsInterface::class);

        $secret_key = $settings->get('secret_key');

        $token = authenticateUser($this->get(PDO::class), $data['usuario'], md5($data['senha']), $secret_key);        

        if ($token) {
            return $response->withJson(['success' => true, 'token' => $token, 'usuario' => $data['usuario']]);
        } else {
            return $response->withStatus(401)->withJson(['error' => 'Credenciais inválidas']);
        }
    });

    $app->group('/cliente', function ($app) use ($validarTokenMiddleware) {
        $app->get('/{id}', Cliente\GetClienteById::class)->use($validarTokenMiddleware);
        $app->post('', Cliente\PostCliente::class)->use($validarTokenMiddleware); 
        $app->put('/{id}', Cliente\PutCliente::class)->use($validarTokenMiddleware);
        $app->delete('/{id}', Cliente\DeleteCliente::class)->use($validarTokenMiddleware);
    });

    $app->group('/cliente_contato', function ($app) use ($validarTokenMiddleware) {
        $app->post('', ClienteContato\PostClienteContato::class)->use($validarTokenMiddleware); 
        $app->put('/{id}', ClienteContato\PutClienteCOntato::class)->use($validarTokenMiddleware);
        $app->delete('/{id}', ClienteContato\DeleteClienteCOntato::class)->use($validarTokenMiddleware);
    });

    $app->group('/cliente_contato_email', function ($app) use ($validarTokenMiddleware) {
        $app->post('', ClienteContatoEmail\PostClienteContatoEmail::class)->use($validarTokenMiddleware); 
        $app->put('/{id}', ClienteContatoEmail\PutClienteContatoEmail::class)->use($validarTokenMiddleware);
        $app->delete('/{id}', ClienteContatoEmail\DeleteClienteContatoEmail::class)->use($validarTokenMiddleware);
    });

    $app->group('/cliente_contato_telefone', function ($app) use ($validarTokenMiddleware) {
        $app->post('', ClienteContatoTelefone\PostClienteContatoTelefone::class)->use($validarTokenMiddleware); 
        $app->put('/{id}', ClienteContatoTelefone\PutClienteContatoTelefone::class)->use($validarTokenMiddleware);
        $app->delete('/{id}', ClienteContatoTelefone\DeleteClienteContatoTelefone::class)->use($validarTokenMiddleware);
    });

    $app->group('/cliente_email', function ($app) use ($validarTokenMiddleware) {
        $app->post('', ClienteEmail\PostClienteEmail::class)->use($validarTokenMiddleware); 
        $app->put('/{id}', ClienteEmail\PutClienteEmail::class)->use($validarTokenMiddleware);
        $app->delete('/{id}', ClienteEmail\DeleteClienteEmail::class)->use($validarTokenMiddleware);
    });

    $app->group('/cliente_endereco', function ($app) use ($validarTokenMiddleware) {
        $app->post('', ClienteEndereco\PostClienteEndereco::class)->use($validarTokenMiddleware); 
        $app->put('/{id}', ClienteEndereco\PutClienteEndereco::class)->use($validarTokenMiddleware);
        $app->delete('/{id}', ClienteEndereco\DeleteClienteEndereco::class)->use($validarTokenMiddleware);
    });

    $app->group('/cliente_extra', function ($app) use ($validarTokenMiddleware) {
        $app->post('', ClienteExtra\PostClienteExtra::class)->use($validarTokenMiddleware); 
        $app->put('/{id}', ClienteExtra\PutClienteExtra::class)->use($validarTokenMiddleware);
        $app->delete('/{id}', ClienteExtra\DeleteClienteExtra::class)->use($validarTokenMiddleware);
    });

    $app->group('/cliente_telefone', function ($app) use ($validarTokenMiddleware) {
        $app->post('', ClienteTelefone\PostClienteTelefone::class)->use($validarTokenMiddleware); 
        $app->put('/{id}', ClienteTelefone\PutClienteTelefone::class)->use($validarTokenMiddleware);
        $app->delete('/{id}', ClienteTelefone\DeleteClienteTelefone::class)->use($validarTokenMiddleware);
    });

    $app->group('/condicao_pagamento', function ($app) use ($validarTokenMiddleware) {
        $app->get('', CondicaoPagamento\GetCondicaoPagamento::class)->use($validarTokenMiddleware); 
        $app->post('', CondicaoPagamento\PostCondicaoPagamento::class)->use($validarTokenMiddleware); 
        $app->put('/{id}', CondicaoPagamento\PutCondicaoPagamento::class)->use($validarTokenMiddleware);
        $app->delete('/{id}', CondicaoPagamento\DeleteCondicaoPagamento::class)->use($validarTokenMiddleware);
    });

    $app->group('/forma_pagamento', function ($app) use ($validarTokenMiddleware) {
        $app->get('', FormaPagamento\GetFormaPagamento::class)->use($validarTokenMiddleware); 
        $app->post('', FormaPagamento\PostFormaPagamento::class)->use($validarTokenMiddleware); 
        $app->put('/{id}', FormaPagamento\PutFormaPagamento::class)->use($validarTokenMiddleware);
        $app->delete('/{id}', FormaPagamento\DeleteFormaPagamento::class)->use($validarTokenMiddleware);
    });

    $app->group('/usuario', function ($app) use ($validarTokenMiddleware) {
        $app->get('', Usuario\GetUsuario::class);
        $app->get('/{id}', Usuario\GetUsuarioId::class);
        $app->post('', Usuario\PostUsuario::class); 
        $app->put('/{id}', Usuario\PutUsuarioId::class);
        $app->delete('/{id}', Usuario\DeleteUsuarioId::class);
    });


    $app->get('/statustoken', function (Request $request, Response $response) {
        try {
            require_once __DIR__ . '/../src/Auth/validate.php';
            ValidarToken($request);
            return $response->withHeader('Content-Type', 'application/json')->withJson(['token' => true]);
        } catch (Exception $e) {
            return $response->withStatus($e->getCode())->withJson(['error' => $e->getMessage()]);
        }
    });

    $app->options('/{routes:.+}', function ($request, $response, $args) {
        return $response;
    });
};
