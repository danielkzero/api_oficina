<?php
namespace App\Application\Handlers\TabelaPreco;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class PutTabelaPreco
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

            $acrescimo = isset($data['acrescimo']) ? $data['acrescimo'] : null;
            $tipo = $data['tipo'];
            $nome = $data['nome'];
            $ultima_alteracao = $data['ultima_alteracao'];
            $excluido = isset($data['excluido']) ? (bool) $data['excluido'] : false;
            $desconto = isset($data['desconto']) ? $data['desconto'] : null;

            $stmt = $this->pdo->prepare("UPDATE tabela_preco SET acrescimo = :acrescimo, tipo = :tipo, nome = :nome, ultima_alteracao = :ultima_alteracao, excluido = :excluido, desconto = :desconto WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':acrescimo', $acrescimo);
            $stmt->bindParam(':tipo', $tipo);
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':ultima_alteracao', $ultima_alteracao);
            $stmt->bindParam(':excluido', $excluido, PDO::PARAM_BOOL);
            $stmt->bindParam(':desconto', $desconto);

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