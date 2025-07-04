<?php
namespace App\Application\Handlers\Api;

use Slim\App;
use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Application\Settings\SettingsInterface;

require_once __DIR__ . '/../../../Auth/functions.php';

class Usuarios
{
    public static function registerRoutes(App $app, $validarTokenMiddleware)
    {
        $container = $app->getContainer();

        $app->group('/usuarios', function ($group) use ($container, $validarTokenMiddleware) {

            // Criar novo usuário global + empresa + vínculo
            $group->post('', function (Request $request, Response $response) use ($container) {
                $pdo = $container->get(PDO::class);
                $data = $request->getParsedBody();

                $email = $data['email'] ?? null;
                $senha = $data['senha'] ?? null;
                $nome = $data['nome'] ?? null;
                $empresa_nome = $data['empresa'] ?? null;

                if (!$email || !$senha || !$empresa_nome) {
                    return self::json($response, ['error' => 'Email, senha e empresa são obrigatórios.'], 400);
                }

                // Verificar se email já existe
                $stmt = $pdo->prepare("SELECT id FROM com_usuario WHERE email = :email");
                $stmt->execute(['email' => $email]);
                if ($stmt->fetch()) {
                    return self::json($response, ['error' => 'Usuário já existe.'], 409);
                }

                // Criar usuário
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO com_usuario (nome, email, senha, tipo, ativo) VALUES (:nome, :email, :senha, 'usuario', 1)");
                $stmt->execute(['nome' => $nome, 'email' => $email, 'senha' => $senha_hash]);
                $usuario_id = $pdo->lastInsertId();

                // Criar empresa
                $chave = bin2hex(random_bytes(12));
                $stmt = $pdo->prepare("INSERT INTO com_empresa (nome, chave_acesso, ativo) VALUES (:nome, :chave, 1)");
                $stmt->execute(['nome' => $empresa_nome, 'chave' => $chave]);
                $empresa_id = $pdo->lastInsertId();

                // Vincular usuário à empresa
                $stmt = $pdo->prepare("INSERT INTO com_usuario_empresa (usuario_id, empresa_id, nivel_acesso, ativo) VALUES (:usuario, :empresa, 'admin', 1)");
                $stmt->execute(['usuario' => $usuario_id, 'empresa' => $empresa_id]);

                return self::json($response, ['success' => true, 'usuario_id' => $usuario_id, 'empresa_id' => $empresa_id]);
            });

            // Login com multiempresa
            $group->post('/auth', function (Request $request, Response $response) use ($container) {
                $pdo = $container->get(PDO::class);
                $settings = $container->get(SettingsInterface::class);
                $secretKey = $settings->get('secret_key');

                $data = $request->getParsedBody();
                $email = $data['email'] ?? null;
                $senha = $data['senha'] ?? null;
                $empresa_id = $data['empresa_id'] ?? null;

                if (!$email || !$senha) {
                    return self::json($response, ['error' => 'Email e senha obrigatórios.'], 400);
                }

                $stmt = $pdo->prepare("SELECT * FROM com_usuario WHERE email = :email AND ativo = 1");
                $stmt->execute(['email' => $email]);
                $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$usuario || !password_verify($senha, $usuario['senha'])) {
                    return self::json($response, ['error' => 'Credenciais inválidas.'], 401);
                }

                // Buscar empresas vinculadas
                $stmt = $pdo->prepare("
                    SELECT e.id, e.nome, u.nivel_acesso
                    FROM com_usuario_empresa u
                    JOIN com_empresa e ON e.id = u.empresa_id
                    WHERE u.usuario_id = :uid AND u.ativo = 1
                ");
                $stmt->execute(['uid' => $usuario['id']]);
                $empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (count($empresas) === 0) {
                    return self::json($response, ['error' => 'Usuário não possui empresa vinculada.'], 403);
                }

                // Se empresa_id não for fornecida
                if (!$empresa_id) {
                    $empresa_id = $empresas;
                }

                $array_usuario = [
                    'id' => $usuario['id'],
                    'nome' => $usuario['nome'],
                    'email' => $usuario['email'],
                    'tipo' => $usuario['tipo'],
                    
                    'empresa_id' => $empresa_id,
                    'nivel_acesso' => $valida[0]['nivel_acesso'] ?? 'usuario',

                    'config' => json_decode($usuario['config'] ?? '{}', true) ?: [],
                ];

                $token = generate_jwt_token( $usuario['id'], $secretKey);
                return self::json($response, ['token' => $token, 'usuario' => $array_usuario]);
            });

            // Obter dados do usuário logado
            $group->get('/eu', function (Request $request, Response $response) use ($container) {
                $pdo = $container->get(PDO::class);

                $user_id = ValidarToken($request);
                $stmt = $pdo->prepare("SELECT id, nome, email, tipo FROM com_usuario WHERE id = :id");
                $stmt->execute(['id' => $user_id]);
                $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

                return self::json($response, $usuario);
            })->add($validarTokenMiddleware);
        });
    }

    private static function json(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
