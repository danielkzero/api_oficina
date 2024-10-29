<?php
namespace App\Application\Handlers\Relatorio\Pedido;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class GetRelatorioSemanal 
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    DAY(data_emissao) AS dia,
                    LEFT(DAYNAME(data_emissao), 1) AS diaSemana,
                    SUM(total) AS vendas
                FROM 
                    pedido
                WHERE 
                    data_emissao >= CURDATE() - INTERVAL 1 MONTH 
                    AND excluido = 0
                GROUP BY 
                    data_emissao
                ORDER BY 
                    data_emissao
            ");
            $stmt->execute();
            $relatorio = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withJson($relatorio);
        } catch (Exception $e) {
            return $response
                ->withStatus(500)
                ->withJson([
                    "statusCode" => 500,
                    "error" => [
                        "type" => "SERVER_ERROR",
                        "description" => $e->getMessage()
                    ]
                ]);
        }
    }
}
