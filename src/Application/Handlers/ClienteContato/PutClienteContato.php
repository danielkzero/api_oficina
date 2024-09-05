<?php
namespace App\Application\Handlers\ClienteContato;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class PutClienteContato
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        try {
            $contato_id = (int)$args['contato_id'];
            $data = $request->getParsedBody();
            
            $nome = $data['nome'];
            $cargo = $data['cargo'];

            $stmt = $this->pdo->prepare("UPDATE cliente_contato SET nome = :nome, cargo = :cargo WHERE id = :id");
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':cargo', $cargo);
            $stmt->bindParam(':id', $contato_id);

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
