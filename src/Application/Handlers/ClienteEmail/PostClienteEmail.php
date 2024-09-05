<?php
namespace App\Application\Handlers\ClienteEmail;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class PostClienteEmail
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        try {
            $cliente_id = (int) $args['cliente_id'];
            $data = $request->getParsedBody();

            $email = $data['email'];
            $tipo = $data['tipo'];

            $stmt = $this->pdo->prepare("INSERT INTO cliente_email (cliente_id, email, tipo) VALUES (:cliente_id, :email, :tipo)");
            $stmt->bindParam(':cliente_id', $cliente_id);
            $stmt->bindParam(':email', $email);
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