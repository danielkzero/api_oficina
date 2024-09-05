<?php
namespace App\Application\Handlers\ProdutoImagem;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class GetProdutoImagemById
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        $id = (int)$args['id'];

        $stmt = $this->pdo->prepare("SELECT * FROM produto_imagem WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $imagem = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($imagem) {
            return $response->withHeader('Content-Type', 'application/json')->withJson($imagem);
        } else {
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json')->withJson(['status' => 'Imagem não encontrada']);
        }
    }
}
