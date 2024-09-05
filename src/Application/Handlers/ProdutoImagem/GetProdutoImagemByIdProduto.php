<?php
namespace App\Application\Handlers\ProdutoImagem;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class GetProdutoImagemByIdProduto
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        $produto_id = (int)$args['produto_id'];

        $stmt = $this->pdo->prepare("SELECT * FROM produto_imagem WHERE produto_id = :produto_id");
        $stmt->execute([':produto_id' => $produto_id]);
        $imagens = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $response->withHeader('Content-Type', 'application/json')->withJson($imagens);
    }
}
