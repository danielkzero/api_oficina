<?php
namespace App\Application\Handlers\ClienteTelefone;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class PostClienteTelefone
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        try {
            $cliente_id = (int)$args['cliente_id'];
            $data = $request->getParsedBody();
        
            $numero = $data['numero'];
            $tipo = $data['tipo'];
        
            $stmt = $this->pdo->prepare("INSERT INTO cliente_telefone (cliente_id, numero, tipo) VALUES (:cliente_id, :numero, :tipo)");
            $stmt->bindParam(':cliente_id', $cliente_id);
            $stmt->bindParam(':numero', $numero);
            $stmt->bindParam(':tipo', $tipo);

            if ($stmt->execute()) {
                $cliente_id = $this->pdo->lastInsertId();
                return $response->withHeader('Content-Type', 'application/json')->withJson(['status' => 'success'], 201);
            } else {
                return $response->withHeader('Content-Type', 'application/json')->withJson(['status' => 'error'], 500);
            }

        } catch (Exception $e) {
            return $response->withStatus($e->getCode())->withJson(['error' => $e->getMessage()]);
        }
    }
}