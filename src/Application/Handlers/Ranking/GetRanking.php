<?php
//routes.php
namespace App\Application\Handlers\Ranking;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class GetRanking
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        try {
            $id = $args['id'];
            
            $stmt = $this->pdo->prepare('SELECT 
            SUM(a.quantidade) AS QtdBilhete, 
            b.nome AS Nome 
            FROM pedidos AS a 
            INNER JOIN clientes AS b ON a.id_cliente=b.id
            INNER JOIN campanhas AS c ON c.id=a.id_campanha
            WHERE c.slug = :slug
            GROUP BY a.id_cliente 
            ORDER BY QtdBilhete DESC LIMIT 3');
            $stmt->bindParam(':slug', $id);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $response->withHeader('Content-Type', 'application/json')->withJson($result);
        } catch (Exception $e) {
            return $response->withStatus($e->getCode())->withJson(['error' => $e->getMessage()]);
        }
    }
}