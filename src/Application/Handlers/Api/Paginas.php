<?php

namespace App\Application\Handlers\Api;

use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Paginas
{
    public static function registerRoutes(App $app, $validarTokenMiddleware)
    {
        $container = $app->getContainer();

        $app->group('/paginas_publicas', function ($group) use ($container, $validarTokenMiddleware) {

            // GET - Listar páginas públicas
            $group->get('', function (Request $request, Response $response) use ($container) {
                $pdo = $container->get(\PDO::class);
                $secret_key = $container->get(\App\Application\Settings\SettingsInterface::class)->get('secret_key');

                $stmt = $pdo->prepare("SELECT 
                        p.id, p.titulo, p.slug, p.conteudo, p.iv, p.status, p.criado_em, p.atualizado_em,
                        m.meta_title, m.html_attrs, m.meta_tags, m.link_tags, m.script_tags, m.style_tags, categoria_id,
                        c.nome AS categoria
                    FROM mod_pages p
                    LEFT JOIN mod_pages_meta m ON m.page_id = p.id
                    LEFT JOIN mod_categoria c ON c.id = p.categoria_id
                    ORDER BY p.criado_em DESC");
                $stmt->execute();
                $pages = $stmt->fetchAll(\PDO::FETCH_ASSOC);

                foreach ($pages as &$page) {
                    $user_key = generate_user_key($secret_key, $page['iv']);
                    $page['conteudo'] = decrypt_content($page['conteudo'], $user_key, $page['iv']);

                    // Decodifica os campos JSON
                    $jsonFields = ['html_attrs', 'meta_tags', 'link_tags', 'script_tags', 'style_tags'];
                    foreach ($jsonFields as $field) {
                        $page[$field] = json_decode($page[$field] ?? '[]', true);
                    }
                }

                $response->getBody()->write(json_encode($pages));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            });

            // POST - Criar nova página
            $group->post('', function (Request $request, Response $response) use ($container) {
                $pdo = $container->get(\PDO::class);
                $usuario_id = ValidarToken($request);
                $data = $request->getParsedBody();

                $titulo = $data['titulo'] ?? null;
                $slug = $data['slug'] ?? null;
                $conteudo = $data['conteudo'] ?? null;
                $meta = $data['meta'] ?? [];


                if (!$titulo || !$slug || !$conteudo) {
                    $response->getBody()->write(json_encode(['error' => 'Campos obrigatórios ausentes.']));
                    return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
                }

                $iv = generate_iv();
                $user_key = generate_user_key($container->get(\App\Application\Settings\SettingsInterface::class)->get('secret_key'), $iv);
                $conteudo_criptografado = encrypt_content($conteudo, $user_key, $iv);

                $pdo->beginTransaction();

                $stmt = $pdo->prepare("INSERT INTO mod_pages (usuario_id, titulo, slug, conteudo, iv, status, categoria_id) 
                    VALUES (:usuario_id, :titulo, :slug, :conteudo, :iv, :status, :categoria_id)");
                $stmt->execute([
                    'usuario_id' => $usuario_id,
                    'titulo' => $titulo,
                    'slug' => $slug,
                    'conteudo' => $conteudo_criptografado,
                    'iv' => $iv,
                    'status' => $data['status'] ?? 'rascunho',
                    'categoria_id' => $data['categoria_id'] ?? null
                ]);

                $page_id = $pdo->lastInsertId();

                $jsonFields = ['html_attrs', 'meta_tags', 'link_tags', 'script_tags', 'style_tags'];
                foreach ($jsonFields as $field) {
                    if (isset($meta[$field]) && is_array($meta[$field])) {
                        $meta[$field] = json_encode($meta[$field], JSON_UNESCAPED_UNICODE);
                    }
                }

                $stmt_meta = $pdo->prepare("INSERT INTO mod_pages_meta 
                    (page_id, meta_title, html_attrs, meta_tags, link_tags, script_tags, style_tags)
                    VALUES (:page_id, :meta_title, :html_attrs, :meta_tags, :link_tags, :script_tags, :style_tags)");
                $stmt_meta->execute(array_merge(['page_id' => $page_id], $meta));

                $pdo->commit();

                $response->getBody()->write(json_encode(['success' => true, 'page_id' => $page_id]));
                return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
            })->add($validarTokenMiddleware);

            // PUT - Atualizar página
            $group->put('/{id}', function (Request $request, Response $response, array $args) use ($container) {
                $pdo = $container->get(\PDO::class);
                $usuario_id = ValidarToken($request);
                $id = (int)$args['id'];
                $data = $request->getParsedBody();

                $stmt = $pdo->prepare("SELECT * FROM mod_pages WHERE id = :id AND usuario_id = :usuario_id");
                $stmt->execute(['id' => $id, 'usuario_id' => $usuario_id]);
                $pagina = $stmt->fetch(\PDO::FETCH_ASSOC);

                if (!$pagina) {
                    $response->getBody()->write(json_encode(['error' => 'Página não encontrada.']));
                    return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
                }

                $iv = $pagina['iv'];
                $user_key = generate_user_key($container->get(\App\Application\Settings\SettingsInterface::class)->get('secret_key'), $iv);
                $conteudo_criptografado = encrypt_content($data['conteudo'] ?? '', $user_key, $iv);

                $pdo->beginTransaction();

                $stmt = $pdo->prepare("UPDATE mod_pages 
                    SET titulo = :titulo, slug = :slug, conteudo = :conteudo, status = :status 
                    WHERE id = :id AND usuario_id = :usuario_id");
                $stmt->execute([
                    'titulo' => $data['titulo'] ?? $pagina['titulo'],
                    'slug' => $data['slug'] ?? $pagina['slug'],
                    'conteudo' => $conteudo_criptografado,
                    'status' => $data['status'] ?? 'rascunho',
                    'id' => $id,
                    'usuario_id' => $usuario_id
                ]);

                if (isset($data['meta'])) {
                    $meta = $data['meta'];
                    $jsonFields = ['html_attrs', 'meta_tags', 'link_tags', 'script_tags', 'style_tags'];
                    foreach ($jsonFields as $field) {
                        if (isset($meta[$field]) && is_array($meta[$field])) {
                            $meta[$field] = json_encode($meta[$field], JSON_UNESCAPED_UNICODE);
                        }
                    }

                    $stmt_meta = $pdo->prepare("UPDATE mod_pages_meta SET 
                        meta_title = :meta_title, html_attrs = :html_attrs, meta_tags = :meta_tags,
                        link_tags = :link_tags, script_tags = :script_tags, style_tags = :style_tags
                        WHERE page_id = :page_id");
                    $stmt_meta->execute(array_merge(['page_id' => $id], $meta));
                }

                $pdo->commit();

                $response->getBody()->write(json_encode(['success' => true]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            })->add($validarTokenMiddleware);

            // DELETE - Excluir página
            $group->delete('/{id}', function (Request $request, Response $response, array $args) use ($container) {
                $pdo = $container->get(\PDO::class);
                $usuario_id = ValidarToken($request);
                $id = (int)$args['id'];

                $stmt = $pdo->prepare("DELETE FROM mod_pages WHERE id = :id AND usuario_id = :usuario_id");
                $stmt->execute(['id' => $id, 'usuario_id' => $usuario_id]);

                $response->getBody()->write(json_encode(['success' => true]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            })->add($validarTokenMiddleware);
        });
    }
}
