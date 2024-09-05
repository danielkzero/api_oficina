<?php
namespace App\Application\Handlers\ClienteContato;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class PostClienteContato
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

            $nome = $data['nome'];
            $cargo = $data['cargo'];
            $excluido = (bool) $data['excluido'];

            $stmt = $this->pdo->prepare("INSERT INTO cliente_contato (cliente_id, nome, cargo, excluido) VALUES (:cliente_id, :nome, :cargo, :excluido)");
            $stmt->bindParam(':cliente_id', $cliente_id);
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':cargo', $cargo);
            $stmt->bindParam(':excluido', $excluido, PDO::PARAM_BOOL);


            if ($stmt->execute()) {
                $contato_id = $this->pdo->lastInsertId();
                return $response->withHeader('Content-Type', 'application/json')->withJson(['status' => 'success', 'id' => $contato_id], 201);
            } else {
                return $response->withHeader('Content-Type', 'application/json')->withJson(['status' => 'error'], 500);
            }

        } catch (Exception $e) {
            return $response->withStatus($e->getCode())->withJson(['error' => $e->getMessage()]);
        }
    }
}