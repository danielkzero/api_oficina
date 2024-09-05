<?php
namespace App\Application\Handlers\ClienteExtra;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class PutClienteExtra
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        try {
            $extra_id = (int)$args['extra_id'];
            $data = $request->getParsedBody();

            $campo_extra_id = (int)$data['campo_extra_id'];
            $nome = $data['nome'];
            $valor_texto = $data['valor_texto'] ?? null;
            $valor_data = $data['valor_data'] ?? null;
            $nome_arquivo = $data['nome_arquivo'] ?? null;
            $valor_arquivo = $data['valor_arquivo'] ?? null;
            $valor_decimal = $data['valor_decimal'] ?? null;

            $stmt = $this->pdo->prepare("UPDATE cliente_extra SET campo_extra_id = :campo_extra_id, nome = :nome, valor_texto = :valor_texto, valor_data = :valor_data, nome_arquivo = :nome_arquivo, valor_arquivo = :valor_arquivo, valor_decimal = :valor_decimal WHERE id = :id");
            $stmt->bindParam(':campo_extra_id', $campo_extra_id);
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':valor_texto', $valor_texto);
            $stmt->bindParam(':valor_data', $valor_data);
            $stmt->bindParam(':nome_arquivo', $nome_arquivo);
            $stmt->bindParam(':valor_arquivo', $valor_arquivo);
            $stmt->bindParam(':valor_decimal', $valor_decimal);
            $stmt->bindParam(':id', $extra_id);

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
