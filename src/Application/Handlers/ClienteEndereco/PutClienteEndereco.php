<?php
namespace App\Application\Handlers\ClienteEndereco;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class PutClienteEndereco
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        try {
            $endereco_id = (int)$args['endereco_id'];
            $data = $request->getParsedBody();
            
            $endereco = $data['endereco'];
            $numero = $data['numero'] ?? null;
            $complemento = $data['complemento'] ?? null;
            $bairro = $data['bairro'] ?? null;
            $cidade = $data['cidade'] ?? null;
            $estado = $data['estado'] ?? null;
            $cep = $data['cep'] ?? null;
            $ultima_alteracao = $data['ultima_alteracao'];

            $stmt = $this->pdo->prepare("UPDATE cliente_endereco SET endereco = :endereco, numero = :numero, complemento = :complemento, bairro = :bairro, cidade = :cidade, estado = :estado, cep = :cep, ultima_alteracao = :ultima_alteracao WHERE id = :id");
            $stmt->bindParam(':endereco', $endereco);
            $stmt->bindParam(':numero', $numero);
            $stmt->bindParam(':complemento', $complemento);
            $stmt->bindParam(':bairro', $bairro);
            $stmt->bindParam(':cidade', $cidade);
            $stmt->bindParam(':estado', $estado);
            $stmt->bindParam(':cep', $cep);
            $stmt->bindParam(':ultima_alteracao', $ultima_alteracao);
            $stmt->bindParam(':id', $endereco_id);

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
