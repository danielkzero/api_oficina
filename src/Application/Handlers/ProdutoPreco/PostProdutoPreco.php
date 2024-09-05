<?php
namespace App\Application\Handlers\ProdutoPreco;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class PostProdutoPreco
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

            $tabela_id = (int) $data['tabela_id'];
            $produto_id = (int) $data['produto_id'];
            $preco = $data['preco'];
            $ultima_alteracao = $data['ultima_alteracao'];
            $excluido = isset($data['excluido']) ? (bool) $data['excluido'] : false;

            $stmt = $this->pdo->prepare("INSERT INTO tabela_preco_produto (tabela_id, produto_id, preco, ultima_alteracao, excluido) VALUES (:tabela_id, :produto_id, :preco, :ultima_alteracao, :excluido)");
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