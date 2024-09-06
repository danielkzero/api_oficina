<?php
namespace App\Application\Handlers\TabelaProdutoPreco;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class GetTabelaProdutoPreco
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM tabela_preco_produto");
            $precosProdutos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $response->withHeader('Content-Type', 'application/json')->withJson($precosProdutos);
        } catch (Exception $e) {
            return $response->withStatus($e->getCode())->withJson(['error' => $e->getMessage()]);
        }
    }
}