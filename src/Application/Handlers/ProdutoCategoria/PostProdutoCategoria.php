<?php
namespace App\Application\Handlers\ProdutoCategoria;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class PostProdutoCategoria
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response)
    {
        $data = $request->getParsedBody();

        $stmt = $this->pdo->prepare("INSERT INTO produto_categoria (nome, categoria_pai_id, ultima_alteracao, excluido) VALUES (:nome, :categoria_pai_id, :ultima_alteracao, :excluido)");

        $stmt->execute([
            ':nome' => $data['nome'],
            ':categoria_pai_id' => $data['categoria_pai_id'] ?? null,
            ':ultima_alteracao' => $data['ultima_alteracao'],
            ':excluido' => $data['excluido']
        ]);

        $id = $this->pdo->lastInsertId();

        return $response->withHeader('Content-Type', 'application/json')->withJson(['status' => 'Categoria criada com sucesso', 'id' => $id]);
    }
}
