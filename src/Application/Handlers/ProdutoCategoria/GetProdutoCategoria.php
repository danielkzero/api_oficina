<?php
namespace App\Application\Handlers\ProdutoCategoria;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class GetProdutoCategoria
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response)
    {
        $stmt = $this->pdo->query("SELECT * FROM produto_categoria WHERE excluido = 0 ORDER BY id DESC");
        $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $response->withHeader('Content-Type', 'application/json')->withJson($categorias);
    }
}
