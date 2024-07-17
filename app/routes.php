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
//require_once __DIR__ . '/../scripts/check_pix_payments.php';

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
            ->write('<strong>API 2024</strong> - v.1');
        return $response;
    });


    $app->post('/login', function (Request $request, Response $response) {
        $data = $request->getParsedBody();
    
        $msg = authenticateUser($this->get(PDO::class), $data['email'], md5($data['senha']));
        if ($msg) {
            // Gerar código aleatório de 6 dígitos
            $codigo = rand(100000, 999999);
    
            // Salvar o código no banco de dados para o usuário
            $pdo = $this->get(PDO::class);
            $stmt = $pdo->prepare("UPDATE usuarios SET codigo = :codigo WHERE email = :email");
            $stmt->execute(['codigo' => $codigo, 'email' => $data['email']]);
    
            // Obter configurações do SMTP do banco de dados
            $smtpConfig = [];
            $stmt = $pdo->prepare("SELECT meta_field, meta_value FROM informacoes_sistema WHERE meta_field IN ('smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass')");
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
            foreach ($results as $row) {
                $smtpConfig[$row['meta_field']] = $row['meta_value'];
            }
    
            // Enviar código para o email do usuário usando PHPMailer
            $mail = new PHPMailer(true);
            try {
                // Configurações do servidor
                $mail->isSMTP();
                $mail->Host = $smtpConfig['smtp_host'];
                $mail->SMTPAuth = true;
                $mail->Username = $smtpConfig['smtp_user'];
                $mail->Password = $smtpConfig['smtp_pass'];
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Usar SSL
                $mail->Port = $smtpConfig['smtp_port'];
    
                // Destinatários
                $mail->setFrom($smtpConfig['smtp_user'], 'no-reponse');
                $mail->addAddress($data['email']);
    
                // Conteúdo do email
                $mail->isHTML(true);
                $mail->CharSet = 'UTF-8';
                $mail->Subject = mb_encode_mimeheader("Seu código de verificação", 'UTF-8');
                $mail->Body = "
                    <html>
                    <head>
                        <style>
                            .email-container {
                                font-family: Arial, sans-serif;
                                line-height: 1.6;
                                color: #333;
                            }
                            .email-header {
                                background-color: #f8f8f8;
                                padding: 10px;
                                text-align: center;
                                font-size: 18px;
                                font-weight: bold;
                            }
                            .email-body {
                                padding: 20px;
                            }
                            .email-footer {
                                background-color: #f8f8f8;
                                padding: 10px;
                                text-align: center;
                                font-size: 12px;
                                color: #888;
                            }
                            .verification-code {
                                display: block;
                                font-size: 24px;
                                margin: 20px 0;
                                text-align: center;
                                color: #0056b3;
                                font-weight: bold;
                            }
                        </style>
                    </head>
                    <body>
                        <div class='email-container'>
                            <div class='email-header'>
                                Código de Verificação de Dois Fatores
                            </div>
                            <div class='email-body'>
                                <p>Olá,</p>
                                <p>Recebemos uma solicitação de verificação em duas etapas para sua conta. Use o código abaixo para concluir o processo de login:</p>
                                <h1 class='verification-code'>$codigo</h1>
                                <p>Se você não solicitou este código, por favor, ignore este e-mail.</p>
                                <p>Atenciosamente,<br>Equipe Fretando App</p>
                            </div>
                            <div class='email-footer'>
                                Este é um e-mail automático, por favor, não responda.
                            </div>
                        </div>
                    </body>
                    </html>";
    
                $mail->send();
                return $response->withJson(
                    [
                        'mensagem' => 'Código de verificação enviado para seu email',
                        'id' => $msg
                    ]
                );
            } catch (Exception $e) {
                return $response->withStatus(500)->withJson(['error' => 'Erro ao enviar email: ' . $mail->ErrorInfo]);
            }
        } else {
            return $response->withStatus(401)->withJson(['error' => 'Credenciais inválidas']);
        }
    });

    // Rota para verificar código de autenticação de dois fatores
    $app->post('/verify-code', function (Request $request, Response $response) {
        $data = $request->getParsedBody();

        $pdo = $this->get(PDO::class);

        global $container;

        $settings = $container->get(SettingsInterface::class);

        $secret_key = $settings->get('secret_key');

        $token = verifyCode($pdo, $data['email'], $data['codigo'], $secret_key);

        if ($token) {
            return $response->withJson(['success' => true, 'token' => $token['token'], 'usuario' => $token['usuario']]);
        } else {
            return $response->withJson(['success' => false, 'error' => 'Código de verificação inválido']);
        }
    });

    $app->group('/usuario', function ($app) use ($validarTokenMiddleware) {
        $app->get('', Usuario\GetUsuario::class)->add($validarTokenMiddleware);
        $app->get('{id}', Usuario\GetUsuarioId::class);
        $app->post('', Usuario\PostUsuario::class); //POST não é protegido, porque qualquer um pode se cadastrar sem ter credencial.
        $app->put('{id}', Usuario\PutUsuarioId::class);
        $app->delete('{id}', Usuario\DeleteUsuarioId::class);
    });

    $app->group('/campanha', function ($app) use ($validarTokenMiddleware) {
        $app->get('', Campanha\GetCampanha::class);
        $app->get('/encerrada', Campanha\GetCampanhaEncerrada::class);
        $app->get('/todas', Campanha\GetCampanhaTodas::class);
        $app->get('/destaque', Campanha\GetCampanhaDestaque::class);
        $app->get('/{id}', Campanha\GetCampanhaId::class);
        $app->get('/slug/{id}', Campanha\GetCampanhaSlug::class);

        $app->post('', Campanha\PostCampanha::class)->add($validarTokenMiddleware); //POST não é protegido, porque qualquer um pode se cadastrar sem ter credencial.        
        $app->put('/{id}', Campanha\PutCampanhaId::class)->add($validarTokenMiddleware);
        $app->delete('/{id}', Campanha\DeleteCampanhaId::class)->add($validarTokenMiddleware);

        $app->get('/{id}/images', Campanha\GetCampanhaImages::class)->add($validarTokenMiddleware);
        $app->post('/{id}/upload', Campanha\PostCampanhaImages::class)->add($validarTokenMiddleware);
        $app->delete('/{id}/images/delete', Campanha\DeleteCampanhaImages::class)->add($validarTokenMiddleware);
    });

    $app->group('/informacoes_sistema', function ($app) use ($validarTokenMiddleware) {
        $app->get('', InformacaoSistema\GetInformacaoSistema::class);
    });
    
    $app->group('/pedido', function ($app) {
        $app->post('', Pedido\PostPedido::class);
    });

    $app->group('/pagamento', function ($app) use ($validarTokenMiddleware) {
        $app->get('/{id}', Pagamento\GetPagamento::class);
    });

    $app->group('/bilhetes', function ($app) use ($validarTokenMiddleware) {
        $app->get('/{id}', Bilhetes\GetBilhetes::class);
    });

    $app->group('/ranking', function ($app) {
        $app->get('/{id}', Ranking\GetRanking::class);
    });

    $app->group('/checar_pagamento', function ($app) use ($validarTokenMiddleware) {
        $app->get('', Pagamento\ChecarPagamento::class);
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

    // No arquivo routes.php ou onde suas rotas estão definidas
    $app->get('/transacao/{id}', function ($request, $response, $args) {
        $id = $args['id'];
        $pdo = $this->get(PDO::class);

        try {
            $stmt = $pdo->prepare("SELECT status FROM pagamentos WHERE url_ref = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $pagamento = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($pagamento) {
                return $response->withJson(['status' => $pagamento['status']]);
            } else {
                return $response->withStatus(404)->withJson(['error' => 'Pagamento não encontrado']);
            }
        } catch (Exception $e) {
            return $response->withStatus(500)->withJson(['error' => $e->getMessage()]);
        }
    });





    $app->post('/aprovarTeste/{id}', function ($request, $response, $args) {
        $paymentId = $args['id'];

        $accessToken = 'TEST-2149374380388094-062308-7850e61af88ebbdc93faf88e8c0ebf50-153788603';
        $client = new Client();
        $url = "https://api.mercadopago.com/v1/payments/$paymentId";

        try {
            // Obtém o estado do pagamento antes da simulação
            $res = $client->get($url, [
                'headers' => [
                    'Authorization' => "Bearer $accessToken",
                    'Content-Type' => 'application/json'
                ]
            ]);

            $body = $res->getBody();
            $paymentData = json_decode($body, true);

            // Verifica se o pagamento está no ambiente sandbox
            if (isset($paymentData['sandbox_init_point'])) {
                // Endpoint para simular o pagamento
                $simulationUrl = "https://api.mercadopago.com/v1/payments/$paymentId/test_user";

                $simRes = $client->post($simulationUrl, [
                    'headers' => [
                        'Authorization' => "Bearer $accessToken",
                        'Content-Type' => 'application/json'
                    ]
                ]);

                $simBody = $simRes->getBody();
                $simData = json_decode($simBody, true);

                return $response->withJson($simData, 200);
            } else {
                return $response->withJson(['error' => 'Payment not in sandbox mode'], 400);
            }
        } catch (\Exception $e) {
            return $response->withJson(['error' => $e->getMessage()], 500);
        }
    });


    //####
    $app->options('/{routes:.+}', function ($request, $response, $args) {
        return $response;
    });
};
