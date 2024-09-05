<?php
namespace App\Application\Handlers\ICMS_ST;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class GetICMS_ST
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        $id = (int)$args['id'];

        try {
            // Get ICMS_ST
            $stmt = $this->pdo->prepare("SELECT * FROM icms_st WHERE id = :id AND excluido = 0");
            $stmt->execute([':id' => $id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($data) {
                return $response->withHeader('Content-Type', 'application/json')->withJson($data);
            } else {
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json')->withJson(['status' => 'ICMS_ST não encontrado']);
            }

        } catch (\Exception $e) {
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json')->withJson(['status' => 'Erro ao buscar ICMS_ST', 'error' => $e->getMessage()]);
        }
    }
}
