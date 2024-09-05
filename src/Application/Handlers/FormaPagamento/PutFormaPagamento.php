<?php
namespace App\Application\Handlers\FormaPagamento;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class PutFormaPagamento
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
            $data = $request->getParsedBody();

            $nome = $data['nome'];
            $excluido = isset($data['excluido']) ? (bool)$data['excluido'] : false;
            $ultima_alteracao = date('Y-m-d H:i:s');

            $stmt = $this->pdo->prepare("UPDATE forma_pagamento SET nome = :nome, excluido = :excluido, ultima_alteracao = :ultima_alteracao WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':excluido', $excluido);
            $stmt->bindParam(':ultima_alteracao', $ultima_alteracao);

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
