<?php
namespace App\Application\Handlers\Cliente;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class GetCliente
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM cliente");
            $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $response->withHeader('Content-Type', 'application/json')->withJson($clientes);
        } catch (Exception $e) {
            return $response->withStatus($e->getCode())->withJson(['error' => $e->getMessage()]);
        }
    }
}