<?php
namespace App\Application\Handlers\ProdutoImagem;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class PutProdutoImagem
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

        $stmt = $this->pdo->prepare("UPDATE produto_imagem SET produto_id = :produto_id, imagem_base64 = :imagem_base64, ordem = :ordem WHERE id = :id");

        $stmt->execute([
            ':produto_id' => $data['produto_id'],
            ':imagem_base64' => $data['imagem_base64'],
            ':ordem' => $data['ordem'],
            ':id' => $id
        ]);

        return $response->withHeader('Content-Type', 'application/json')->withJson(['status' => 'Imagem atualizada com sucesso']);
    }
}
