<?php
namespace App\Application\Handlers\ProdutoImagem;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class DeleteProdutoImagem
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        $id = (int)$args['id'];

        $stmt = $this->pdo->prepare("DELETE FROM produto_imagem WHERE id = :id");
        $stmt->execute([':id' => $id]);

        return $response->withHeader('Content-Type', 'application/json')->withJson(['status' => 'Imagem deletada com sucesso']);
    }
}
