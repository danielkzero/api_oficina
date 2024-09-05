<?php
namespace App\Application\Handlers\ProdutoImagem;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class PostProdutoImagem
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response)
    {
        $data = $request->getParsedBody();

        $stmt = $this->pdo->prepare("INSERT INTO produto_imagem (produto_id, imagem_base64, ordem) VALUES (:produto_id, :imagem_base64, :ordem)");

        $stmt->execute([
            ':produto_id' => $data['produto_id'],
            ':imagem_base64' => $data['imagem_base64'],
            ':ordem' => $data['ordem']
        ]);

        $id = $this->pdo->lastInsertId();

        return $response->withHeader('Content-Type', 'application/json')->withJson(['status' => 'Imagem adicionada com sucesso', 'id' => $id]);
    }
}
