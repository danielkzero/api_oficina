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
            ->write('<strong>GESTOR MOBILE ONLINE 2024</strong> - v.1');
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
        $app->get('/{id}', Cliente\GetClienteById::class);
        $app->post('', Cliente\PostCliente::class); 
        $app->put('/{id}', Cliente\PutCliente::class);
        $app->delete('/{id}', Cliente\DeleteCliente::class);
    })->add($validarTokenMiddleware);

    $app->group('/cliente_contato', function ($app) use ($validarTokenMiddleware) {
        $app->post('', ClienteContato\PostClienteContato::class); 
        $app->put('/{id}', ClienteContato\PutClienteCOntato::class);
        $app->delete('/{id}', ClienteContato\DeleteClienteCOntato::class);
    })->add($validarTokenMiddleware);

    $app->group('/cliente_contato_email', function ($app) use ($validarTokenMiddleware) {
        $app->post('', ClienteContatoEmail\PostClienteContatoEmail::class); 
        $app->put('/{id}', ClienteContatoEmail\PutClienteContatoEmail::class);
        $app->delete('/{id}', ClienteContatoEmail\DeleteClienteContatoEmail::class);
    })->add($validarTokenMiddleware);

    $app->group('/cliente_contato_telefone', function ($app) use ($validarTokenMiddleware) {
        $app->post('', ClienteContatoTelefone\PostClienteContatoTelefone::class); 
        $app->put('/{id}', ClienteContatoTelefone\PutClienteContatoTelefone::class);
        $app->delete('/{id}', ClienteContatoTelefone\DeleteClienteContatoTelefone::class);
    })->add($validarTokenMiddleware);

    $app->group('/cliente_email', function ($app) use ($validarTokenMiddleware) {
        $app->post('', ClienteEmail\PostClienteEmail::class); 
        $app->put('/{id}', ClienteEmail\PutClienteEmail::class);
        $app->delete('/{id}', ClienteEmail\DeleteClienteEmail::class);
    })->add($validarTokenMiddleware);

    $app->group('/cliente_endereco', function ($app) use ($validarTokenMiddleware) {
        $app->post('', ClienteEndereco\PostClienteEndereco::class); 
        $app->put('/{id}', ClienteEndereco\PutClienteEndereco::class);
        $app->delete('/{id}', ClienteEndereco\DeleteClienteEndereco::class);
    })->add($validarTokenMiddleware);

    $app->group('/cliente_extra', function ($app) use ($validarTokenMiddleware) {
        $app->post('', ClienteExtra\PostClienteExtra::class); 
        $app->put('/{id}', ClienteExtra\PutClienteExtra::class);
        $app->delete('/{id}', ClienteExtra\DeleteClienteExtra::class);
    });

    $app->group('/cliente_telefone', function ($app) use ($validarTokenMiddleware) {
        $app->post('', ClienteTelefone\PostClienteTelefone::class); 
        $app->put('/{id}', ClienteTelefone\PutClienteTelefone::class);
        $app->delete('/{id}', ClienteTelefone\DeleteClienteTelefone::class);
    })->add($validarTokenMiddleware);

    $app->group('/condicao_pagamento', function ($app) use ($validarTokenMiddleware) {
        $app->get('', CondicaoPagamento\GetCondicaoPagamento::class); 
        $app->post('', CondicaoPagamento\PostCondicaoPagamento::class); 
        $app->put('/{id}', CondicaoPagamento\PutCondicaoPagamento::class);
        $app->delete('/{id}', CondicaoPagamento\DeleteCondicaoPagamento::class);
    });

    $app->group('/forma_pagamento', function ($app) use ($validarTokenMiddleware) {
        $app->get('', FormaPagamento\GetFormaPagamento::class); 
        $app->post('', FormaPagamento\PostFormaPagamento::class); 
        $app->put('/{id}', FormaPagamento\PutFormaPagamento::class);
        $app->delete('/{id}', FormaPagamento\DeleteFormaPagamento::class);
    })->add($validarTokenMiddleware);

    $app->group('/icms_st', function ($app) use ($validarTokenMiddleware) {
        $app->get('/{id}', ICMS_ST\GetICMS_STById::class); 
        $app->post('', ICMS_ST\PostICMS_ST::class); 
        $app->put('/{id}', ICMS_ST\PutICMS_ST::class);
        $app->delete('/{id}', ICMS_ST\DeleteICMS_ST::class);
    })->add($validarTokenMiddleware);

    $app->group('/pedido', function ($app) use ($validarTokenMiddleware) {
        $app->get('', Pedido\GetPedido::class); 
        $app->get('/{id}', Pedido\GetPedidoById::class); 
        $app->post('', Pedido\PostPedido::class); 
        $app->put('/{id}', Pedido\PutPedido::class);
        $app->delete('/{id}', Pedido\DeletePedido::class);
    })->add($validarTokenMiddleware);

    $app->group('/produto', function ($app) use ($validarTokenMiddleware) {
        $app->get('', Produto\GetProdutos::class); 
        $app->get('/{id}', Produto\GetProdutoById::class); 
        $app->post('', Produto\PostProduto::class); 
        $app->put('/{id}', Produto\PutProduto::class);
        $app->delete('/{id}', Produto\DeleteProduto::class);
    })->add($validarTokenMiddleware);

    $app->group('/produto_categoria', function ($app) use ($validarTokenMiddleware) {
        $app->get('', ProdutoCategoria\GetProdutoCategoria::class); 
        $app->get('/{id}', ProdutoCategoria\GetProdutoCategoriaById::class); 
        $app->post('', ProdutoCategoria\PostProdutoCategoria::class); 
        $app->put('/{id}', ProdutoCategoria\PutProdutoCategoria::class);
        $app->delete('/{id}', ProdutoCategoria\DeleteProdutoCategoria::class);
    })->add($validarTokenMiddleware);

    $app->group('/produto_imagem', function ($app) use ($validarTokenMiddleware) {
        $app->get('', ProdutoImagem\GetProdutoImagem::class); 
        $app->get('/{produto_id}', ProdutoImagem\GetProdutoImagemByIdProduto::class); 
        $app->post('', ProdutoImagem\PostProdutoImagem::class); 
        $app->put('/{id}', ProdutoImagem\PutProdutoImagem::class);
        $app->delete('/{id}', ProdutoImagem\DeleteProdutoImagem::class);
    })->add($validarTokenMiddleware);

    $app->group('/tabela_preco', function ($app) use ($validarTokenMiddleware) {
        $app->get('', TabelaPreco\GetTabelaPreco::class); 
        $app->post('', TabelaPreco\PostTabelaPreco::class); 
        $app->put('/{id}', TabelaPreco\PutTabelaPreco::class);
        $app->delete('/{id}', TabelaPreco\DeleteTabelaPreco::class);
    })->add($validarTokenMiddleware);

    $app->group('/tabela_produto_preco', function ($app) use ($validarTokenMiddleware) {
        $app->get('', TabelaProdutoPreco\GetTabelaProdutoPreco::class); 
        $app->post('', TabelaProdutoPreco\PostTabelaProdutoPreco::class); 
        $app->put('/{id}', TabelaProdutoPreco\PutTabelaProdutoPreco::class);
        $app->delete('/{id}', TabelaProdutoPreco\DeleteTabelaProdutoPreco::class);
    })->add($validarTokenMiddleware);

    $app->group('/usuario', function ($app) use ($validarTokenMiddleware) {
        $app->get('', Usuario\GetUsuario::class);
        $app->get('/{id}', Usuario\GetUsuarioId::class);
        $app->post('', Usuario\PostUsuario::class); 
        $app->put('/{id}', Usuario\PutUsuarioId::class);
        $app->delete('/{id}', Usuario\DeleteUsuarioId::class);
    })->add($validarTokenMiddleware);


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
