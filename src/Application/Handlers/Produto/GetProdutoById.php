<?php
namespace App\Application\Handlers\Produto;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class GetProdutoById
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        $id = (int)$args['id'];

        $stmt = $this->pdo->prepare("SELECT * FROM produto WHERE id = :id ORDER BY id DESC");
        $stmt->execute([':id' => $id]);
        $produto = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($produto) {
            return $response->withHeader('Content-Type', 'application/json')->withJson($produto);
        } else {
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json')->withJson(['status' => 'Produto não encontrado']);
        }
    }
}
