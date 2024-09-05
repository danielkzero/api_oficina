<?php
namespace App\Application\Handlers\ClienteContatoTelefone;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class PutClienteContatoTelefone
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        try {
            $telefone_id = (int)$args['telefone_id'];
            $data = $request->getParsedBody();
            
            $numero = $data['numero'];
            $tipo = $data['tipo'];

            $stmt = $this->pdo->prepare("UPDATE cliente_contato_telefone SET numero = :numero, tipo = :tipo WHERE id = :id");
            $stmt->bindParam(':numero', $numero);
            $stmt->bindParam(':tipo', $tipo);
            $stmt->bindParam(':id', $telefone_id);

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
