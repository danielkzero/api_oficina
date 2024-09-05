<?php
namespace App\Application\Handlers\ProdutoImagem;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class PostProdutoImagem
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        try {
            $produto_id = (int)$args['id'];
            $data = $request->getParsedBody();
            
            $imagem_base64 = $data['imagem_base64'];
            $ordem = (int)$data['ordem'];
        
            $stmt = $this->pdo->prepare("INSERT INTO produto_imagem (produto_id, imagem_base64, ordem) VALUES (:produto_id, :imagem_base64, :ordem)");
            $stmt->bindParam(':produto_id', $produto_id);
            $stmt->bindParam(':imagem_base64', $imagem_base64);
            $stmt->bindParam(':ordem', $ordem);

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