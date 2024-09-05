<?php
namespace App\Application\Handlers\ClienteContatoEmail;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class PutClienteContatoEmail
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        try {
            $contato_email_id = (int)$args['contato_email_id'];
            $data = $request->getParsedBody();
            
            $email = $data['email'];
            $tipo = $data['tipo'];

            $stmt = $this->pdo->prepare("UPDATE cliente_contato_email SET email = :email, tipo = :tipo WHERE id = :id");
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':tipo', $tipo);
            $stmt->bindParam(':id', $contato_email_id);

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
