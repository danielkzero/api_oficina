<?php
namespace App\Application\Handlers\TabelaPrecoCidade;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class GetTabelaPrecoCidade
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT tpc.*, tp.nome tabela FROM tabela_preco_cidade tpc LEFT JOIN tabela_preco tp ON tp.id=tpc.id_tabela_preco ORDER BY id DESC");
            $stmt->execute();

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $response->withHeader('Content-Type', 'application/json')->withJson($result);
        } catch (Exception $e) {
            return $response->withStatus(500)->withJson(['error' => $e->getMessage()]);
        }
    }
}
