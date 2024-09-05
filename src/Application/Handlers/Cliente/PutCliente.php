<?php
namespace App\Application\Handlers\Cliente;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class PutCliente
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        try {
            $id = (int) $args['id'];
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

            $stmt = $this->pdo->prepare("UPDATE cliente SET tipo = :tipo, razao_social = :razao_social, nome_fantasia = :nome_fantasia, cnpj = :cnpj, inscricao_estadual = :inscricao_estadual, rua = :rua, numero = :numero, complemento = :complemento, bairro = :bairro, cidade = :cidade, estado = :estado, cep = :cep, suframa = :suframa, observacao = :observacao, ultima_alteracao = :ultima_alteracao, excluido = :excluido, bloqueado = :bloqueado, motivo_bloqueio_id = :motivo_bloqueio_id WHERE id = :id");
            $stmt->bindParam(':id', $id);
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
                return $response->withHeader('Content-Type', 'application/json')->withJson(['status' => 'success']);
            } else {
                return $response->withHeader('Content-Type', 'application/json')->withJson(['status' => 'error'], 500);
            }
        } catch (Exception $e) {
            return $response->withStatus($e->getCode())->withJson(['error' => $e->getMessage()]);
        }
    }
}