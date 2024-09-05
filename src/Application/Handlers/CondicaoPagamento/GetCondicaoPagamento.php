<?php
namespace App\Application\Handlers\CondicaoPagamento;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class GetCondicaoPagamento
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        try {
            $stmt = $this->pdo->query("SELECT * FROM condicao_pagamento");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $response->withHeader('Content-Type', 'application/json')->withJson($data);
        } catch (Exception $e) {
            return $response->withStatus($e->getCode())->withJson(['error' => $e->getMessage()]);
        }
    }
}
