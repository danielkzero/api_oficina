<?php
namespace App\Application\Handlers\ClienteEmail;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class PutClienteEmail
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        try {
            $cliente_email_id = (int)$args['email_id'];
            $data = $request->getParsedBody();
            
            $email = $data['email'];
            $tipo = $data['tipo'];

            // Atualizar o e-mail com base no ID fornecido
            $stmt = $this->pdo->prepare("
                UPDATE cliente_email
                SET email = :email, tipo = :tipo
                WHERE id = :id
            ");
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':tipo', $tipo);
            $stmt->bindParam(':id', $cliente_email_id);

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
