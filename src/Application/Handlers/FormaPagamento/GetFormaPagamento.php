<?php
namespace App\Application\Handlers\FormaPagamento;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class GetFormaPagamento
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        try {
            $stmt = $this->pdo->query("SELECT * FROM forma_pagamento");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $response->withHeader('Content-Type', 'application/json')->withJson($data);
        } catch (Exception $e) {
            return $response->withStatus($e->getCode())->withJson(['error' => $e->getMessage()]);
        }
    }
}
