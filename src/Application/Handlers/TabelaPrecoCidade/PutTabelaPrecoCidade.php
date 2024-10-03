<?php
namespace App\Application\Handlers\TabelaPrecoCidade;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class PutTabelaPrecoCidade
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
            $data = $request->getParsedBody();
            $id_tabela_preco = $data['id_tabela_preco'];
            $id_ibge_cidade = $data['id_ibge_cidade'];

            $stmt = $this->pdo->prepare("UPDATE tabela_preco_cidade SET id_tabela_preco = :id_tabela_preco, id_ibge_cidade = :id_ibge_cidade WHERE id = :id");
            $stmt->bindParam(':id_tabela_preco', $id_tabela_preco);
            $stmt->bindParam(':id_ibge_cidade', $id_ibge_cidade);
            $stmt->bindParam(':id', $id);

            if ($stmt->execute()) {
                return $response->withHeader('Content-Type', 'application/json')->withJson(['status' => 'updated'], 200);
            } else {
                return $response->withHeader('Content-Type', 'application/json')->withJson(['status' => 'error'], 500);
            }
        } catch (Exception $e) {
            return $response->withStatus(500)->withJson(['error' => $e->getMessage()]);
        }
    }
}
