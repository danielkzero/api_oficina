<?php
namespace App\Application\Handlers\ClienteContatoTelefone;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class PostClienteContatoTelefone
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        try {
            $contato_id = (int) $args['contato_id'];
            $data = $request->getParsedBody();

            $numero = $data['numero'];
            $tipo = $data['tipo'];

            $stmt = $this->pdo->prepare("INSERT INTO cliente_contato_telefone (contato_id, numero, tipo) VALUES (:contato_id, :numero, :tipo)");
            $stmt->bindParam(':contato_id', $contato_id);
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