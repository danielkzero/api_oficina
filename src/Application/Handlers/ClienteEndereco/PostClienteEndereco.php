<?php
namespace App\Application\Handlers\ClienteEndereco;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class PostClienteEndereco
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

            $endereco = $data['endereco'];
            $numero = $data['numero'];
            $complemento = $data['complemento'];
            $bairro = $data['bairro'];
            $cidade = $data['cidade'];
            $estado = $data['estado'];
            $cep = $data['cep'];
            $ultima_alteracao = $data['ultima_alteracao'];

            $stmt = $this->pdo->prepare("INSERT INTO cliente_endereco (cliente_id, endereco, numero, complemento, bairro, cidade, estado, cep, ultima_alteracao) VALUES (:cliente_id, :endereco, :numero, :complemento, :bairro, :cidade, :estado, :cep, :ultima_alteracao)");
            $stmt->bindParam(':cliente_id', $cliente_id);
            $stmt->bindParam(':endereco', $endereco);
            $stmt->bindParam(':numero', $numero);
            $stmt->bindParam(':complemento', $complemento);
            $stmt->bindParam(':bairro', $bairro);
            $stmt->bindParam(':cidade', $cidade);
            $stmt->bindParam(':estado', $estado);
            $stmt->bindParam(':cep', $cep);
            $stmt->bindParam(':ultima_alteracao', $ultima_alteracao);

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