<?php
namespace App\Application\Handlers\ClienteTelefone;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class DeleteClienteTelefone
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        try {
            $cliente_telefone_id = (int)$args['telefone_id'];

            // Excluir o telefone com base no ID fornecido
            $stmt = $this->pdo->prepare("DELETE FROM cliente_telefone WHERE id = :id");
            $stmt->bindParam(':id', $cliente_telefone_id);

            if ($stmt->execute()) {
                return $response->withHeader('Content-Type', 'application/json')->withJson(['status' => 'success'], 200);
            } else {
                return $response->withHeader('Content-Type', 'application/json')->withJson(['status' => 'error'], 500);
            }

        } catch (Exception $e) {
            return $response->withStatus($e->getCode())->withJson(['error' => $e->getMessage()]);
        }
    }
}
