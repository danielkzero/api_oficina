<?php
namespace App\Application\Handlers\ProdutoCategoria;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class GetProdutoCategoriaById
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        $id = (int)$args['id'];

        $stmt = $this->pdo->prepare("SELECT * FROM produto_categoria WHERE id = :id AND excluido = 0 ORDER BY id DESC");
        $stmt->execute([':id' => $id]);
        $categoria = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($categoria) {
            return $response->withHeader('Content-Type', 'application/json')->withJson($categoria);
        } else {
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json')->withJson(['status' => 'Categoria não encontrada']);
        }
    }
}
