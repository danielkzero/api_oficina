<?php
//routes.php
namespace App\Application\Handlers\TabelaPreco;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class GetTabelaPrecoIdIbge
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

            $stmt = $this->pdo->prepare('SELECT * FROM tabela_preco_cidade tpc 
                LEFT JOIN tabela_preco tp ON tp.id=tpc.id_tabela_preco 
                WHERE tpc.id_ibge_cidade = :id');
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $response->withHeader('Content-Type', 'application/json')->withJson($result);
        } catch (Exception $e) {
            return $response->withStatus($e->getCode())->withJson(['error' => $e->getMessage()]);
        }
    }

}