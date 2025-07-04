<?php
namespace App\Application\Handlers\Api;

use Slim\App;
use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Esquema
{
    public static function registerRoutes(App $app, $validarTokenMiddleware)
    {
        $container = $app->getContainer();

        $app->group('/esquema', function ($group) use ($container, $validarTokenMiddleware) {

            // GET /esquema/interface/{slug}
            $group->get('/interface/{slug}', function (Request $request, Response $response, $args) use ($container) {
                $pdo = $container->get(PDO::class);
                $slug = $args['slug'];

                $stmt = $pdo->prepare("SELECT * FROM esquema_interface WHERE slug = :slug AND status = 1");
                $stmt->execute(['slug' => $slug]);
                $interface = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$interface) {
                    return self::json($response, ['error' => 'Interface não encontrada.'], 404);
                }

                $stmt = $pdo->prepare("SELECT * FROM esquema_interface_modulo WHERE interface_id = :id AND status = 1 ORDER BY ordem");
                $stmt->execute(['id' => $interface['id']]);
                $modulos = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($modulos as &$modulo) {
                    $stmtComp = $pdo->prepare("SELECT * FROM esquema_interface_componente WHERE modulo_id = :modulo_id AND status = 1 ORDER BY ordem");
                    $stmtComp->execute(['modulo_id' => $modulo['id']]);
                    $modulo['componentes'] = $stmtComp->fetchAll(PDO::FETCH_ASSOC);
                }

                $interface['modulos'] = $modulos;
                return self::json($response, $interface);
            })->add($validarTokenMiddleware);

            // GET /esquema/modulo/{modulo_id}/dados
            $group->get('/modulo/{modulo_id}/dados', function (Request $request, Response $response, $args) use ($container) {
                $pdo = $container->get(PDO::class);
                $modulo_id = $args['modulo_id'];

                $stmt = $pdo->prepare("SELECT * FROM esquema_interface_dado WHERE modulo_id = :modulo_id AND status = 1 ORDER BY atualizado_em DESC");
                $stmt->execute(['modulo_id' => $modulo_id]);
                $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

                return self::json($response, $dados);
            })->add($validarTokenMiddleware);

            // POST /esquema/modulo/{modulo_id}/dados
            $group->post('/modulo/{modulo_id}/dados', function (Request $request, Response $response, $args) use ($container) {
                $pdo = $container->get(PDO::class);
                $modulo_id = $args['modulo_id'];
                $data = $request->getParsedBody();

                $dados = json_encode($data['dados'] ?? []);
                $empresa_id = $data['empresa_id'] ?? null;

                if (!$empresa_id) {
                    return self::json($response, ['error' => 'empresa_id obrigatório.'], 400);
                }

                $stmt = $pdo->prepare("
                    INSERT INTO esquema_interface_dado (modulo_id, empresa_id, dados, status)
                    VALUES (:modulo_id, :empresa_id, :dados, 1)
                ");
                $stmt->execute([
                    'modulo_id' => $modulo_id,
                    'empresa_id' => $empresa_id,
                    'dados' => $dados
                ]);

                return self::json($response, ['success' => true, 'id' => $pdo->lastInsertId()]);
            })->add($validarTokenMiddleware);

            // PUT /esquema/modulo/{modulo_id}/dados/{dado_id}
            $group->put('/modulo/{modulo_id}/dados/{dado_id}', function (Request $request, Response $response, $args) use ($container) {
                $pdo = $container->get(PDO::class);
                $modulo_id = $args['modulo_id'];
                $dado_id = $args['dado_id'];
                $data = $request->getParsedBody();

                $dados = json_encode($data['dados'] ?? []);

                $stmt = $pdo->prepare("
                    UPDATE esquema_interface_dado 
                    SET dados = :dados, atualizado_em = CURRENT_TIMESTAMP 
                    WHERE id = :id AND modulo_id = :modulo_id
                ");
                $stmt->execute([
                    'id' => $dado_id,
                    'modulo_id' => $modulo_id,
                    'dados' => $dados
                ]);

                return self::json($response, ['success' => true]);
            })->add($validarTokenMiddleware);

            // DELETE /esquema/modulo/{modulo_id}/dados/{dado_id}
            $group->delete('/modulo/{modulo_id}/dados/{dado_id}', function (Request $request, Response $response, $args) use ($container) {
                $pdo = $container->get(PDO::class);
                $modulo_id = $args['modulo_id'];
                $dado_id = $args['dado_id'];

                $stmt = $pdo->prepare("DELETE FROM esquema_interface_dado WHERE id = :id AND modulo_id = :modulo_id");
                $stmt->execute([
                    'id' => $dado_id,
                    'modulo_id' => $modulo_id
                ]);

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
