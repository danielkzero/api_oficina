<?php
namespace App\Application\Handlers\Cliente;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class PostCliente
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        try {
            $data = $request->getParsedBody();

            // Extraia os dados do cliente
            $tipo = $data['tipo'];
            $razao_social = $data['razao_social'];
            $nome_fantasia = $data['nome_fantasia'];
            $cnpj = $data['cnpj'];
            $inscricao_estadual = $data['inscricao_estadual'];
            $rua = $data['rua'];
            $numero = $data['numero'];
            $complemento = $data['complemento'];
            $bairro = $data['bairro'];
            $cidade = $data['cidade'];
            $estado = $data['estado'];
            $cep = $data['cep'];
            $suframa = $data['suframa'];
            $observacao = $data['observacao'];
            $ultima_alteracao = $data['ultima_alteracao'];
            $excluido = (bool) $data['excluido'];
            $bloqueado = (bool) $data['bloqueado'];
            $motivo_bloqueio_id = isset($data['motivo_bloqueio_id']) ? (int) $data['motivo_bloqueio_id'] : null;

            $stmt = $this->pdo->prepare("INSERT INTO cliente (tipo, razao_social, nome_fantasia, cnpj, inscricao_estadual, rua, numero, complemento, bairro, cidade, estado, cep, suframa, observacao, ultima_alteracao, excluido, bloqueado, motivo_bloqueio_id) VALUES (:tipo, :razao_social, :nome_fantasia, :cnpj, :inscricao_estadual, :rua, :numero, :complemento, :bairro, :cidade, :estado, :cep, :suframa, :observacao, :ultima_alteracao, :excluido, :bloqueado, :motivo_bloqueio_id)");
            $stmt->bindParam(':tipo', $tipo);
            $stmt->bindParam(':razao_social', $razao_social);
            $stmt->bindParam(':nome_fantasia', $nome_fantasia);
            $stmt->bindParam(':cnpj', $cnpj);
            $stmt->bindParam(':inscricao_estadual', $inscricao_estadual);
            $stmt->bindParam(':rua', $rua);
            $stmt->bindParam(':numero', $numero);
            $stmt->bindParam(':complemento', $complemento);
            $stmt->bindParam(':bairro', $bairro);
            $stmt->bindParam(':cidade', $cidade);
            $stmt->bindParam(':estado', $estado);
            $stmt->bindParam(':cep', $cep);
            $stmt->bindParam(':suframa', $suframa);
            $stmt->bindParam(':observacao', $observacao);
            $stmt->bindParam(':ultima_alteracao', $ultima_alteracao);
            $stmt->bindParam(':excluido', $excluido, PDO::PARAM_BOOL);
            $stmt->bindParam(':bloqueado', $bloqueado, PDO::PARAM_BOOL);
            $stmt->bindParam(':motivo_bloqueio_id', $motivo_bloqueio_id);

            if ($stmt->execute()) {
                $cliente_id = $this->pdo->lastInsertId();
                return $response->withHeader('Content-Type', 'application/json')->withJson(['status' => 'success', 'id' => $cliente_id], 201);
            } else {
                return $response->withHeader('Content-Type', 'application/json')->withJson(['status' => 'error'], 500);
            }

        } catch (Exception $e) {
            return $response->withStatus($e->getCode())->withJson(['error' => $e->getMessage()]);
        }
    }
}