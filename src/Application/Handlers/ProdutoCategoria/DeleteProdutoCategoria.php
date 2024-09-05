<?php
namespace App\Application\Handlers\ProdutoCategoria;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class DeleteProdutoCategoria
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

            // Check if the category has child categories
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM produto_categoria WHERE categoria_pai_id = :id");
            $stmt->execute([':id' => $id]);
            $hasChildren = $stmt->fetchColumn();

            if ($hasChildren > 0) {
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json')->withJson(['status' => 'Não é possível excluir uma categoria com categorias filhas']);
            }

            // Delete category
            $stmt = $this->pdo->prepare("DELETE FROM produto_categoria WHERE id = :id");
            $stmt->execute([':id' => $id]);

            // Commit transaction
            $this->pdo->commit();

            return $response->withHeader('Content-Type', 'application/json')->withJson(['status' => 'Categoria deletada com sucesso']);

        } catch (\Exception $e) {
            // Rollback transaction on error
            $this->pdo->rollBack();
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json')->withJson(['status' => 'Erro ao deletar categoria', 'error' => $e->getMessage()]);
        }
    }
}
