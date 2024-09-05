<?php
namespace App\Application\Handlers\FormaPagamento;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class DeleteFormaPagamento
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        try {
            $id = (int)$args['id'];

            $stmt = $this->pdo->prepare("DELETE FROM forma_pagamento WHERE id = :id");
            $stmt->bindParam(':id', $id);

            if ($stmt->execute()) {
                return $response->withHeader('Content-Type', 'application/json')->withJson(['status' => 'success']);
            } else {
                return $response->withHeader('Content-Type', 'application/json')->withJson(['status' => 'error'], 500);
            }

        } catch (Exception $e) {
            return $response->withStatus($e->getCode())->withJson(['error' => $e->getMessage()]);
        }
    }
}
