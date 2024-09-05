<?php
namespace App\Application\Handlers\TabelaPreco;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class GetTabelaPreco
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        try {
            $produto_id = (int)$args['id'];
        
            $stmt = $this->pdo->prepare("SELECT * FROM tabela_preco");
            $stmt->bindParam(':produto_id', $produto_id);
            $stmt->execute();

            $imagens = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $response->withHeader('Content-Type', 'application/json')->withJson($imagens);
        } catch (Exception $e) {
            return $response->withStatus($e->getCode())->withJson(['error' => $e->getMessage()]);
        }
    }
}