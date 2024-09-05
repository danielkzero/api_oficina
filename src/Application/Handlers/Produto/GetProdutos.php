<?php
namespace App\Application\Handlers\Produto;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class GetProdutos
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response)
    {
        $stmt = $this->pdo->query("SELECT * FROM produto");
        $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $response->withHeader('Content-Type', 'application/json')->withJson($produtos);
    }
}
