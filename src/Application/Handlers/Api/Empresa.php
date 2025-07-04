<?php

namespace App\Application\Handlers\Api;

use Slim\App;
use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Empresa
{
    public static function registerRoutes(App $app, $validarTokenMiddleware)
    {
        $container = $app->getContainer();

        $app->group('/empresas', function ($group) use ($container, $validarTokenMiddleware) {

            // Listar empresas vinculadas ao usuário logado
            $group->get('', function (Request $request, Response $response) use ($container) {
                $pdo = $container->get(PDO::class);
                $jwt = ValidarToken($request);

                $usuario_id = $jwt;

                $stmt = $pdo->prepare("
                    SELECT e.id, e.nome, e.chave_acesso, ue.nivel_acesso
                    FROM com_usuario_empresa ue
                    JOIN com_empresa e ON e.id = ue.empresa_id
                    WHERE ue.usuario_id = :usuario_id AND ue.ativo = 1
                ");
                $stmt->execute(['usuario_id' => $usuario_id]);
                $empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);

                return self::json($response, $empresas);
            })->add($validarTokenMiddleware);

            // Criar nova empresa e vincular usuário atual como admin
            $group->post('', function (Request $request, Response $response) use ($container) {
                $pdo = $container->get(PDO::class);
                //$jwt = ValidarToken($request);
                //$usuario_id = $jwt->usuario_id;

                $data = $request->getParsedBody();
                $nome = $data['nome'] ?? null;

                if (!$nome) {
                    return self::json($response, ['error' => 'Nome da empresa é obrigatório.'], 400);
                }

                $chave = bin2hex(random_bytes(12));

                $stmt = $pdo->prepare("INSERT INTO com_empresa (nome, chave_acesso, ativo) VALUES (:nome, :chave, 1)");
                $stmt->execute([
                    'nome' => $nome,
                    'chave' => $chave
                ]);

                $empresa_id = $pdo->lastInsertId();

                // Vincular o usuário como admin da nova empresa
                /*$stmt = $pdo->prepare("INSERT INTO com_usuario_empresa (usuario_id, empresa_id, nivel_acesso, ativo) VALUES (:usuario_id, :empresa_id, 'admin', 1)");
                $stmt->execute([
                    'usuario_id' => $usuario_id,
                    'empresa_id' => $empresa_id
                ]);*/

                return self::json($response, ['success' => true, 'empresa_id' => $empresa_id]);
            });

            // Atualizar nome da empresa (somente admin)
            $group->put('/{id}', function (Request $request, Response $response, array $args) use ($container) {
                $pdo = $container->get(PDO::class);
                $jwt = ValidarToken($request);
                $usuario_id = $jwt->usuario_id;
                $empresa_id = (int) $args['id'];

                $data = $request->getParsedBody();
                $novo_nome = $data['nome'] ?? null;

                if (!$novo_nome) {
                    return self::json($response, ['error' => 'Nome é obrigatório.'], 400);
                }

                // Verifica se usuário tem nível admin nessa empresa
                $stmt = $pdo->prepare("SELECT nivel_acesso FROM com_usuario_empresa WHERE usuario_id = :uid AND empresa_id = :eid AND ativo = 1");
                $stmt->execute(['uid' => $usuario_id, 'eid' => $empresa_id]);
                $nivel = $stmt->fetchColumn();

                if ($nivel !== 'admin') {
                    return self::json($response, ['error' => 'Acesso negado.'], 403);
                }

                $stmt = $pdo->prepare("UPDATE com_empresa SET nome = :nome WHERE id = :id");
                $stmt->execute([
                    'nome' => $novo_nome,
                    'id' => $empresa_id
                ]);

                return self::json($response, ['success' => true]);
            })->add($validarTokenMiddleware);

            // Remover empresa (soft delete)
            $group->delete('/{id}', function (Request $request, Response $response, array $args) use ($container) {
                $pdo = $container->get(PDO::class);
                $jwt = ValidarToken($request);
                $usuario_id = $jwt->usuario_id;
                $empresa_id = (int) $args['id'];

                // Verifica se usuário tem nível admin
                $stmt = $pdo->prepare("SELECT nivel_acesso FROM com_usuario_empresa WHERE usuario_id = :uid AND empresa_id = :eid AND ativo = 1");
                $stmt->execute(['uid' => $usuario_id, 'eid' => $empresa_id]);
                $nivel = $stmt->fetchColumn();

                if ($nivel !== 'admin') {
                    return self::json($response, ['error' => 'Acesso negado.'], 403);
                }

                // Soft delete: desativa empresa
                $stmt = $pdo->prepare("UPDATE com_empresa SET ativo = 0 WHERE id = :id");
                $stmt->execute(['id' => $empresa_id]);

                return self::json($response, ['success' => true]);
            })->add($validarTokenMiddleware);
        });
    }

    private static function json(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
