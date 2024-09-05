<?php
namespace App\Application\Handlers\Produto;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class DeleteProduto
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        $id = (int)$args['id'];

        try {
            // Start transaction
            $this->pdo->beginTransaction();

            // Delete produto_imagem
            $stmt = $this->pdo->prepare("DELETE FROM produto_imagem WHERE produto_id = :id");
            $stmt->execute([':id' => $id]);

            // Delete produto
            $stmt = $this->pdo->prepare("DELETE FROM produto WHERE id = :id");
            $stmt->execute([':id' => $id]);

            // Commit transaction
            $this->pdo->commit();

            return $response->withHeader('Content-Type', 'application/json')->withJson(['status' => 'Produto deletado com sucesso']);

        } catch (\Exception $e) {
            // Rollback transaction on error
            $this->pdo->rollBack();
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json')->withJson(['status' => 'Erro ao deletar produto', 'error' => $e->getMessage()]);
        }
    }
}
