<?php
namespace App\Application\Handlers\ProdutoCategoria;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class PutProdutoCategoria
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        $id = (int)$args['id'];
        $data = $request->getParsedBody();

        $stmt = $this->pdo->prepare("UPDATE produto_categoria SET nome = :nome, categoria_pai_id = :categoria_pai_id, ultima_alteracao = :ultima_alteracao, excluido = :excluido WHERE id = :id");

        $stmt->execute([
            ':nome' => $data['nome'],
            ':categoria_pai_id' => $data['categoria_pai_id'] ?? null,
            ':ultima_alteracao' => $data['ultima_alteracao'],
            ':excluido' => $data['excluido'],
            ':id' => $id
        ]);

        return $response->withHeader('Content-Type', 'application/json')->withJson(['status' => 'Categoria atualizada com sucesso']);
    }
}
