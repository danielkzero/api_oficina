<?php
namespace App\Application\Handlers\TabelaProdutoPreco;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class PutTabelaProdutoPreco
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

            $tabela_id = (int) $data['tabela_id'];
            $produto_id = (int) $data['produto_id'];
            $preco = $data['preco'];
            $ultima_alteracao = $data['ultima_alteracao'];
            $excluido = isset($data['excluido']) ? (bool) $data['excluido'] : false;

            $stmt = $this->pdo->prepare("UPDATE tabela_preco_produto SET tabela_id = :tabela_id, produto_id = :produto_id, preco = :preco, ultima_alteracao = :ultima_alteracao, excluido = :excluido WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':tabela_id', $tabela_id);
            $stmt->bindParam(':produto_id', $produto_id);
            $stmt->bindParam(':preco', $preco);
            $stmt->bindParam(':ultima_alteracao', $ultima_alteracao);
            $stmt->bindParam(':excluido', $excluido, PDO::PARAM_BOOL);

            if ($stmt->execute()) {
                return $response->withHeader('Content-Type', 'application/json')->withJson(['status' => 'success'], 201);
            } else {
                return $response->withHeader('Content-Type', 'application/json')->withJson(['status' => 'error'], 500);
            }
        } catch (Exception $e) {
            return $response->withStatus($e->getCode())->withJson(['error' => $e->getMessage()]);
        }
    }
}