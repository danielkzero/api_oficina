<?php
//routes.php
namespace App\Application\Handlers\Campanha;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class GetCampanhaTodas
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM campanhas WHERE excluido=0 AND publicado=1 ORDER BY Id DESC');
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $response->withHeader('Content-Type', 'application/json')->withJson($result);
        } catch (Exception $e) {
            return $response->withStatus($e->getCode())->withJson(['error' => $e->getMessage()]);
        }
    }
}