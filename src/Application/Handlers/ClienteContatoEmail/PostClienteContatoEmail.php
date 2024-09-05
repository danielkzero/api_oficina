<?php
namespace App\Application\Handlers\ClienteContatoEmail;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class PostClienteContatoEmail
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
            
            $email = $data['email'];
            $tipo = $data['tipo'];

            $stmt = $this->pdo->prepare("INSERT INTO cliente_contato_email (contato_id, email, tipo) VALUES (:contato_id, :email, :tipo)");
            $stmt->bindParam(':contato_id', $contato_id);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':tipo', $tipo);

            if ($stmt->execute()) {
                return $response->withHeader('Content-Type', 'application/json')->withJson(['status' => 'success'], 201);
            } else {
                return $response->withHeader('Content-Type', 'application/json')->withJson(['status' => 'error'], 500);
            }

        } catch (Exception $e) {
            return $response->withStatus($e->getCode())->withJson(['error' => $e->getMessage()]);
        }
    }
}
